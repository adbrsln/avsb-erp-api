<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\ChartOfAccount;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = Bill::with('vendor');

        if (! empty($params['vendor_id'])) {
            $query->where('vendor_id', $params['vendor_id']);
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['vendor_id']) || empty($data['bill_date']) || empty($data['due_date'])) {
            return response()->json(['error' => 'vendor_id, bill_date, and due_date are required'], 422);
        }

        if (! isset($data['items']) || ! is_array($data['items']) || count($data['items']) === 0) {
            return response()->json(['error' => 'items must be a non-empty array'], 422);
        }

        $data['bill_number'] = (new NumberingService)->generate('bill');
        $data['status'] = 'unpaid';

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
        $data['paid_amount'] = 0;
        $data['balance'] = $data['total'];

        $bill = DB::transaction(function () use ($data) {
            $bill = Bill::create(fillableData(new Bill, $data));

            foreach ($data['items'] as $item) {
                BillItem::create([
                    'bill_id' => $bill->id,
                    'description' => $item['description'] ?? '',
                    'unit' => $item['unit'] ?? 'Lot',
                    'quantity' => (float) ($item['quantity'] ?? 1),
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                    'total' => $item['total'],
                    'account_id' => ! empty($item['account_id']) ? (int) $item['account_id'] : null,
                ]);
            }

            $apAccount = ChartOfAccount::where('code', '2101')->first();

            if ($apAccount) {
                $je = JournalEntry::create([
                    'entry_number' => (new NumberingService)->generate('journal'),
                    'entry_date' => $data['bill_date'],
                    'description' => 'Bill received - '.($data['bill_number'] ?? ''),
                    'reference_type' => 'bill',
                    'reference_id' => $bill->id,
                    'status' => 'posted',
                    'posted_at' => Carbon::now(),
                ]);

                foreach ($data['items'] as $item) {
                    $accountId = ! empty($item['account_id']) ? (int) $item['account_id'] : null;
                    if (! $accountId) {
                        $expenseAccount = ChartOfAccount::where('code', '5101')->first();
                        $accountId = $expenseAccount ? $expenseAccount->id : null;
                    }

                    if ($accountId) {
                        JournalEntryLine::create([
                            'journal_entry_id' => $je->id,
                            'account_id' => $accountId,
                            'debit' => $item['total'],
                            'description' => $item['description'] ?? '',
                        ]);
                    }
                }

                JournalEntryLine::create([
                    'journal_entry_id' => $je->id,
                    'account_id' => $apAccount->id,
                    'credit' => $data['total'],
                    'description' => $data['bill_number'] ?? '',
                ]);
            }

            foreach ($data['items'] as $item) {
                if (empty($item['account_id'])) {
                    continue;
                }

                $account = ChartOfAccount::find((int) $item['account_id']);
                if (! $account || $account->type !== 'asset') {
                    continue;
                }

                $invItem = InventoryItem::where('name', $item['description'])
                    ->orWhere('sku', $item['description'])
                    ->first();

                if ($invItem) {
                    $unitCost = (float) ($item['unit_price'] > 0 ? $item['unit_price'] : $invItem->unit_cost);

                    InventoryTransaction::create([
                        'item_id' => $invItem->id,
                        'type' => 'in',
                        'qty' => (float) ($item['quantity'] ?? 1),
                        'unit_cost' => $unitCost,
                        'total_cost' => $item['total'],
                        'reference_type' => 'bill',
                        'reference_id' => $bill->id,
                        'notes' => 'Stock from bill: '.$data['bill_number'],
                    ]);

                    $invItem->increment('stock_qty', (float) ($item['quantity'] ?? 1));
                }
            }

            return $bill;
        });

        $bill->load('vendor', 'items');

        return response()->json($bill, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $bill = Bill::with('vendor', 'items', 'items.account', 'payments', 'payments.debitAccount', 'payments.creditAccount')->findOrFail($id);

        return response()->json($bill);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $bill = Bill::findOrFail($id);

        if ($bill->status !== 'unpaid') {
            return response()->json(['error' => 'Only unpaid bills can be updated'], 422);
        }

        $data = $request->all();
        unset($data['bill_number'], $data['status']);

        if (isset($data['items']) && is_array($data['items'])) {
            $subtotal = 0;
            foreach ($data['items'] as &$item) {
                $qty = (float) ($item['quantity'] ?? 1);
                $price = (float) ($item['unit_price'] ?? 0);
                $item['total'] = round($qty * $price, 2);
                $subtotal += $item['total'];
            }
            unset($item);

            $tax = (float) ($data['tax'] ?? $bill->tax);
            $data['subtotal'] = round($subtotal, 2);
            $data['total'] = round($subtotal + $tax, 2);
            $data['balance'] = round($data['total'] - $bill->paid_amount, 2);
        }

        $bill->update(fillableData($bill, $data));

        if (isset($data['items']) && is_array($data['items'])) {
            BillItem::where('bill_id', $bill->id)->get()->each->delete();
            foreach ($data['items'] as $item) {
                BillItem::create([
                    'bill_id' => $bill->id,
                    'description' => $item['description'] ?? '',
                    'unit' => $item['unit'] ?? 'Lot',
                    'quantity' => (float) ($item['quantity'] ?? 1),
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                    'total' => $item['total'],
                    'account_id' => ! empty($item['account_id']) ? (int) $item['account_id'] : null,
                ]);
            }
        }

        $bill->load('vendor', 'items');

        return response()->json($bill);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $bill = Bill::findOrFail($id);

        $je = JournalEntry::where('reference_type', 'bill')->where('reference_id', $bill->id)->first();
        if ($je) {
            $je->update(['status' => 'voided']);
        }

        BillItem::where('bill_id', $bill->id)->get()->each->delete();
        $bill->delete();

        return response()->json(null, 204);
    }
}
