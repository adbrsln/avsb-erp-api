<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\ChartOfAccount;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use App\Services\DocumentGenerator;
use App\Services\FileStorageService;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
        $po = PurchaseOrder::with('vendor', 'items', 'items.account', 'bill')->findOrFail($id);

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

    public function fromInventory(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['vendor_id']) || empty($data['items']) || ! is_array($data['items'])) {
            return response()->json(['error' => 'vendor_id and items array are required'], 422);
        }

        $vendor = Vendor::find($data['vendor_id']);
        if (! $vendor) {
            return response()->json(['error' => 'Vendor not found'], 404);
        }

        $poItems = [];
        $subtotal = 0;

        foreach ($data['items'] as $selection) {
            $itemId = (int) ($selection['item_id'] ?? 0);
            $qty = (float) ($selection['qty'] ?? 1);

            $invItem = InventoryItem::find($itemId);
            if (! $invItem) {
                return response()->json(['error' => "Inventory item #{$itemId} not found"], 404);
            }

            $unitPrice = (float) ($selection['unit_price'] ?? $invItem->unit_cost);
            $total = round($qty * $unitPrice, 2);
            $subtotal += $total;

            $poItems[] = [
                'description' => $invItem->name,
                'unit' => $invItem->unit ?? 'Lot',
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total' => $total,
            ];
        }

        $tax = (float) ($data['tax'] ?? 0);
        $poData = [
            'vendor_id' => $vendor->id,
            'po_number' => (new NumberingService)->generate('purchase_order'),
            'order_date' => $data['order_date'] ?? date('Y-m-d'),
            'delivery_date' => $data['delivery_date'] ?? null,
            'status' => 'draft',
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'total' => round($subtotal + $tax, 2),
            'notes' => $data['notes'] ?? null,
        ];

        $po = PurchaseOrder::create(fillableData(new PurchaseOrder, $poData));

        foreach ($poItems as $pi) {
            PurchaseOrderItem::create(array_merge($pi, ['purchase_order_id' => $po->id]));
        }

        $po->load('vendor', 'items');

        return response()->json($po, 201);
    }

    public function generateBill(Request $request, int $id): JsonResponse
    {
        $po = PurchaseOrder::with('items', 'vendor')->findOrFail($id);

        if ($po->status !== 'received') {
            return response()->json(['error' => 'Only received purchase orders can generate bills'], 422);
        }

        if ($po->bill) {
            return response()->json(['error' => 'A bill already exists for this purchase order', 'bill' => $po->bill], 422);
        }

        if (! $po->vendor) {
            return response()->json(['error' => 'Purchase order has no vendor'], 422);
        }

        $billData = [
            'bill_number' => (new NumberingService)->generate('bill'),
            'vendor_id' => $po->vendor_id,
            'purchase_order_id' => $po->id,
            'bill_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'status' => 'unpaid',
            'subtotal' => $po->subtotal,
            'tax' => $po->tax,
            'total' => $po->total,
            'paid_amount' => 0,
            'balance' => $po->total,
        ];

        $bill = Bill::create(fillableData(new Bill, $billData));

        foreach ($po->items as $poItem) {
            BillItem::create([
                'bill_id' => $bill->id,
                'description' => $poItem->description,
                'unit' => $poItem->unit,
                'quantity' => $poItem->quantity,
                'unit_price' => $poItem->unit_price,
                'total' => $poItem->total,
                'account_id' => $poItem->account_id,
            ]);
        }

        $bill->load('vendor', 'purchaseOrder', 'items');

        return response()->json($bill, 201);
    }

    public function download(Request $request, int $id): JsonResponse|Response
    {
        $po = PurchaseOrder::with('vendor', 'items')->findOrFail($id);
        $pdf = (new DocumentGenerator)->purchaseOrder($po);

        try {
            $storage = new FileStorageService;
            $path = 'documents/purchase-orders/'.$po->id.'.pdf';
            $storage->put($path, $pdf, 'application/pdf');

            $url = $storage->getPresignedUrl($path);
            if ($url) {
                return response()->json(['url' => $url, 'filename' => $po->po_number.'.pdf']);
            }
        } catch (\Throwable) {
            // Storage unavailable — fall through to binary response
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$po->po_number.'.pdf"',
        ]);
    }
}
