<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = PurchaseOrder::with('vendor');

        if (! empty($params['vendor_id'])) {
            $query->where('vendor_id', $params['vendor_id']);
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['all']) && $params['all'] === 'true') {
            return response()->json(['data' => $query->orderBy('po_number')->get()]);
        }

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['vendor_id']) || empty($data['order_date'])) {
            return response()->json(['error' => 'vendor_id and order_date are required'], 422);
        }

        if (! isset($data['items']) || ! is_array($data['items']) || count($data['items']) === 0) {
            return response()->json(['error' => 'items must be a non-empty array'], 422);
        }

        $data['po_number'] = (new NumberingService)->generate('purchase_order');
        $data['status'] = 'draft';

        $subtotal = 0;
        foreach ($data['items'] as &$item) {
            $qty = (float) ($item['quantity'] ?? 1);
            $price = (float) ($item['unit_price'] ?? 0);
            $item['total'] = round($qty * $price, 2);
            $subtotal += $item['total'];
        }
        unset($item);

        $tax = (float) ($data['tax'] ?? 0);
        $data['subtotal'] = round($subtotal, 2);
        $data['total'] = round($subtotal + $tax, 2);

        $po = PurchaseOrder::create(fillableData(new PurchaseOrder, $data));

        foreach ($data['items'] as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'description' => $item['description'] ?? '',
                'unit' => $item['unit'] ?? 'Lot',
                'quantity' => (float) ($item['quantity'] ?? 1),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'total' => $item['total'],
                'account_id' => ! empty($item['account_id']) ? (int) $item['account_id'] : null,
            ]);
        }

        $po->load('vendor', 'items');

        return response()->json($po, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $po = PurchaseOrder::with('vendor', 'items', 'items.account')->findOrFail($id);

        return response()->json($po);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== 'draft' && $po->status !== 'pending') {
            return response()->json(['error' => 'Only draft or pending purchase orders can be updated'], 422);
        }

        $data = $request->all();
        unset($data['po_number']);

        if (isset($data['items']) && is_array($data['items'])) {
            $subtotal = 0;
            foreach ($data['items'] as &$item) {
                $qty = (float) ($item['quantity'] ?? 1);
                $price = (float) ($item['unit_price'] ?? 0);
                $item['total'] = round($qty * $price, 2);
                $subtotal += $item['total'];
            }
            unset($item);

            $tax = (float) ($data['tax'] ?? $po->tax);
            $data['subtotal'] = round($subtotal, 2);
            $data['total'] = round($subtotal + $tax, 2);
        }

        $po->update(fillableData($po, $data));

        if (isset($data['items']) && is_array($data['items'])) {
            PurchaseOrderItem::where('purchase_order_id', $po->id)->get()->each->delete();
            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'description' => $item['description'] ?? '',
                    'unit' => $item['unit'] ?? 'Lot',
                    'quantity' => (float) ($item['quantity'] ?? 1),
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                    'total' => $item['total'],
                    'account_id' => ! empty($item['account_id']) ? (int) $item['account_id'] : null,
                ]);
            }
        }

        $po->load('vendor', 'items');

        return response()->json($po);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status === 'received') {
            return response()->json(['error' => 'Cannot delete a received purchase order'], 422);
        }

        PurchaseOrderItem::where('purchase_order_id', $po->id)->get()->each->delete();
        $po->delete();

        return response()->json(null, 204);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== 'draft') {
            return response()->json(['error' => 'Only draft purchase orders can be submitted'], 422);
        }

        $po->update(['status' => 'submitted']);

        try {
            $recipients = NotificationRecipientResolver::getApprovers('po');
            NotificationService::queueToMany(
                NotificationEvent::PO_SUBMITTED,
                $recipients,
                [
                    'po_number' => $po->po_number ?? '',
                    'total' => number_format($po->total, 2),
                    'url' => '/finance/purchase-orders/'.$po->id,
                ],
                'App\\Models\\PurchaseOrder',
                $po->id
            );
        } catch (\Throwable $e) {
            logger()->error('Notification failed: po.submitted', ['po_id' => $po->id, 'error' => $e->getMessage()]);
        }

        return response()->json($po);
    }

    public function receive(Request $request, int $id): JsonResponse
    {
        $po = PurchaseOrder::with('items')->findOrFail($id);

        if ($po->status !== 'submitted') {
            return response()->json(['error' => 'Only submitted purchase orders can be received'], 422);
        }

        $po->update(['status' => 'received']);

        foreach ($po->items as $item) {
            if (! $item->account_id) {
                continue;
            }

            $account = ChartOfAccount::find($item->account_id);
            if (! $account || $account->type !== 'asset') {
                continue;
            }

            $invItem = InventoryItem::where('name', $item->description)
                ->orWhere('sku', $item->description)
                ->first();

            if ($invItem) {
                $unitCost = $item->unit_price > 0 ? $item->unit_price : $invItem->unit_cost;

                InventoryTransaction::create([
                    'item_id' => $invItem->id,
                    'type' => 'in',
                    'qty' => $item->quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $item->total,
                    'reference_type' => 'purchase_order',
                    'reference_id' => $po->id,
                    'notes' => 'Received from PO: '.$po->po_number,
                ]);

                $invItem->increment('stock_qty', $item->quantity);
            }
        }

        $po->load('vendor', 'items');

        try {
            $recipients = NotificationRecipientResolver::getByRole(['admin', 'finance']);
            if (! empty($recipients)) {
                NotificationService::queueToMany(
                    NotificationEvent::PO_RECEIVED,
                    $recipients,
                    [
                        'po_number' => $po->po_number ?? '',
                        'url' => '/finance/purchase-orders/'.$po->id,
                    ],
                    'App\\Models\\PurchaseOrder',
                    $po->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: po.received', ['po_id' => $po->id, 'error' => $e->getMessage()]);
        }

        return response()->json($po);
    }
}
