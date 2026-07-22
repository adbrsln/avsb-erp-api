<?php

namespace Database\Seeders;

use App\Helpers\MalaysianDataGenerator as G;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\BillPayment;
use App\Models\ChartOfAccount;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;

class BulkPurchasingSeeder
{
    public function run(): void
    {
        $vendors = Vendor::all();
        $items = InventoryItem::all();
        $coa = ChartOfAccount::all();
        $cashAcct = $coa->where('code', '1102')->first();
        $payableAcct = $coa->where('code', '2101')->first();
        $wipAcct = $coa->where('code', '1106')->first();

        // 30 Purchase Orders
        $poStatuses = ['pending', 'pending', 'received', 'pending', 'received', 'cancelled'];
        $billStatuses = ['unpaid', 'paid', 'unpaid', 'paid', 'overdue'];

        for ($i = 0; $i < 30; $i++) {
            $vendor = $vendors->random();
            $status = $poStatuses[array_rand($poStatuses)];
            $subtotal = G::randomAmount(5000, 150000);
            $tax = round($subtotal * 0.08, 2);

            $po = PurchaseOrder::create([
                'po_number' => 'PO-BULK-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'vendor_id' => $vendor->id,
                'order_date' => G::randomDate('2024-01-01', '2024-12-31'),
                'delivery_date' => G::randomDate('2024-02-01', '2025-01-31'),
                'status' => $status,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
                'notes' => 'Bulk generated PO',
            ]);

            // 1-3 items per PO
            $numItems = rand(1, 3);
            $itemTotal = 0;
            for ($j = 0; $j < $numItems; $j++) {
                $invItem = $items->random();
                $qty = rand(5, 100);
                $total = $qty * $invItem->unit_cost;
                $itemTotal += $total;
                PurchaseOrderItem::insert([
                    'purchase_order_id' => $po->id,
                    'description' => $invItem->name,
                    'unit' => $invItem->unit,
                    'quantity' => $qty,
                    'unit_price' => $invItem->unit_cost,
                    'total' => $total,
                    'account_id' => $wipAcct->id ?? null,
                ]);
            }

            // Create bill for received POs
            if ($status === 'received') {
                $billStatus = $billStatuses[array_rand($billStatuses)];
                $paidAmount = $billStatus === 'paid' ? $subtotal + $tax : 0;

                $bill = Bill::create([
                    'bill_number' => 'BILL-BULK-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'vendor_id' => $vendor->id,
                    'purchase_order_id' => $po->id,
                    'vendor_bill_no' => 'INV-'.str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                    'bill_date' => G::randomDate('2024-01-15', '2024-12-31'),
                    'due_date' => G::randomDate('2024-02-15', '2025-01-31'),
                    'status' => $billStatus,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $subtotal + $tax,
                    'paid_amount' => $paidAmount,
                    'balance' => $subtotal + $tax - $paidAmount,
                ]);

                // Bill items
                for ($j = 0; $j < $numItems; $j++) {
                    $invItem = $items->random();
                    $qty = rand(5, 50);
                    BillItem::insert([
                        'bill_id' => $bill->id,
                        'description' => $invItem->name,
                        'unit' => $invItem->unit,
                        'quantity' => $qty,
                        'unit_price' => $invItem->unit_cost,
                        'total' => $qty * $invItem->unit_cost,
                        'account_id' => $wipAcct->id ?? null,
                    ]);
                }

                // Payment for paid bills
                if ($billStatus === 'paid' && $cashAcct && $payableAcct) {
                    BillPayment::create([
                        'bill_id' => $bill->id,
                        'amount' => $subtotal + $tax,
                        'payment_date' => G::randomDate('2024-02-01', '2024-12-31'),
                        'debit_account_id' => $payableAcct->id,
                        'credit_account_id' => $cashAcct->id,
                        'payment_reference' => 'TT-'.str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                    ]);
                }

                // Stock in transaction
                $invItem = $items->random();
                $qty = rand(10, 100);
                InventoryTransaction::create([
                    'item_id' => $invItem->id,
                    'type' => 'in',
                    'qty' => $qty,
                    'unit_cost' => $invItem->unit_cost,
                    'total_cost' => $qty * $invItem->unit_cost,
                    'reference_type' => 'purchase_order',
                    'reference_id' => $po->id,
                    'notes' => 'Stock in from '.$po->po_number,
                ]);
            }
        }
    }
}
