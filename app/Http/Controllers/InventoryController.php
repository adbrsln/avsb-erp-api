<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = InventoryItem::query();

        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%")
                    ->orWhere('category', 'like', "%{$s}%");
            });
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['low_stock'])) {
            $query->whereColumn('stock_qty', '<=', 'reorder_level');
        }

        if (! empty($params['all']) && $params['all'] === 'true') {
            return response()->json(['data' => $query->orderBy('name')->get()]);
        }

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['sku']) || empty($data['name'])) {
            return response()->json(['error' => 'sku and name are required'], 422);
        }

        $exists = InventoryItem::where('sku', $data['sku'])->exists();
        if ($exists) {
            return response()->json(['error' => 'SKU already exists'], 422);
        }

        $item = InventoryItem::create(fillableData(new InventoryItem, $data));

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = InventoryItem::with('transactions')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $item = InventoryItem::findOrFail($id);

        if (isset($data['sku']) && $data['sku'] !== $item->sku) {
            $duplicate = InventoryItem::where('sku', $data['sku'])->where('id', '!=', $item->id)->exists();
            if ($duplicate) {
                return response()->json(['error' => 'SKU already exists'], 422);
            }
        }

        $item->update(fillableData($item, $data));

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        InventoryItem::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    public function transactions(Request $request, int $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);
        $txns = InventoryTransaction::where('item_id', $item->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $txns]);
    }

    public function adjustStock(Request $request, int $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);
        $data = $request->all();

        if (empty($data['type']) || ! in_array($data['type'], ['in', 'out'])) {
            return response()->json(['error' => 'type must be in or out'], 422);
        }

        $qty = (float) ($data['qty'] ?? 0);
        if ($qty <= 0) {
            return response()->json(['error' => 'qty must be positive'], 422);
        }

        if ($data['type'] === 'out' && $qty > $item->stock_qty) {
            return response()->json(['error' => 'Insufficient stock', 'available' => $item->stock_qty], 422);
        }

        DB::beginTransaction();
        try {
            $txn = InventoryTransaction::create([
                'item_id' => $item->id,
                'type' => $data['type'],
                'qty' => $qty,
                'unit_cost' => $item->unit_cost,
                'total_cost' => r2($qty * $item->unit_cost),
                'reference_type' => 'manual_adjustment',
                'notes' => $data['reason'] ?? '',
            ]);

            if ($data['type'] === 'in') {
                $item->increment('stock_qty', $qty);
            } else {
                $item->decrement('stock_qty', $qty);
            }

            DB::commit();
            $item->refresh();

            return response()->json(['transaction' => $txn, 'item' => $item]);
        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->error('Stock adjustment failed', ['item_id' => $item->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Stock adjustment failed'], 500);
        }
    }
}
