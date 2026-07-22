<?php

namespace App\Seeds;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\BillPayment;
use App\Models\ChartOfAccount;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;

class PurchasingSeeder
{
    public function run(): void
    {
        if (PurchaseOrder::count() > 0) {
            return;
        }

        $vendors = Vendor::all();
        $coa = ChartOfAccount::all();

        // Find relevant COA accounts
        $inventoryAcct = $coa->where('code', '1106')->first(); // Project WIP
        $expenseAcct = $coa->where('code', '6203')->first(); // Office Supplies
        $fuelAcct = $coa->where('code', '6301')->first() ?? $expenseAcct; // Transportation

        // PO 1: Asphalt from Eurovia
        $po1 = PurchaseOrder::create([
            'po_number' => 'PO-2024-001', 'vendor_id' => $vendors->first()->id ?? 1,
            'order_date' => '2024-01-05', 'delivery_date' => '2024-01-15',
            'status' => 'received', 'subtotal' => 120000, 'tax' => 9600, 'total' => 129600,
            'notes' => 'ACW14 Asphalt for Jalan Tun Razak project',
        ]);
        PurchaseOrderItem::insert([
            ['purchase_order_id' => $po1->id, 'description' => 'ACW14 Asphalt', 'unit' => 'Ton', 'quantity' => 500, 'unit_price' => 240, 'total' => 120000, 'account_id' => $inventoryAcct->id ?? null],
        ]);

        // PO 2: Aggregates from Pulai Rock
        $po2 = PurchaseOrder::create([
            'po_number' => 'PO-2024-002', 'vendor_id' => $vendors->skip(1)->first()->id ?? 1,
            'order_date' => '2024-01-10', 'delivery_date' => '2024-01-20',
            'status' => 'received', 'subtotal' => 45000, 'tax' => 3600, 'total' => 48600,
            'notes' => 'Aggregates for Federal Highway project',
        ]);
        PurchaseOrderItem::insert([
            ['purchase_order_id' => $po2->id, 'description' => '20mm Aggregates', 'unit' => 'Ton', 'quantity' => 200, 'unit_price' => 120, 'total' => 24000, 'account_id' => $inventoryAcct->id ?? null],
            ['purchase_order_id' => $po2->id, 'description' => '14mm Aggregates', 'unit' => 'Ton', 'quantity' => 150, 'unit_price' => 140, 'total' => 21000, 'account_id' => $inventoryAcct->id ?? null],
        ]);

        // PO 3: Bitumen from Shell
        $po3 = PurchaseOrder::create([
            'po_number' => 'PO-2024-003', 'vendor_id' => $vendors->skip(2)->first()->id ?? 1,
            'order_date' => '2024-02-01', 'delivery_date' => '2024-02-10',
            'status' => 'pending', 'subtotal' => 84000, 'tax' => 6720, 'total' => 90720,
            'notes' => 'Bitumen 60/70 for upcoming paving works',
        ]);
        PurchaseOrderItem::insert([
            ['purchase_order_id' => $po3->id, 'description' => 'Bitumen 60/70', 'unit' => 'Ton', 'quantity' => 60, 'unit_price' => 1400, 'total' => 84000, 'account_id' => $inventoryAcct->id ?? null],
        ]);

        // PO 4: Equipment rental from UEH
        $po4 = PurchaseOrder::create([
            'po_number' => 'PO-2024-004', 'vendor_id' => $vendors->skip(3)->first()->id ?? 1,
            'order_date' => '2024-02-15', 'delivery_date' => '2024-02-20',
            'status' => 'pending', 'subtotal' => 35000, 'tax' => 2800, 'total' => 37800,
            'notes' => 'Roller rental for 2 months',
        ]);
        PurchaseOrderItem::insert([
            ['purchase_order_id' => $po4->id, 'description' => 'Bomag Roller Rental (Monthly)', 'unit' => 'Month', 'quantity' => 2, 'unit_price' => 17500, 'total' => 35000, 'account_id' => $expenseAcct->id ?? null],
        ]);

        // PO 5: Fuel from Petronas
        $po5 = PurchaseOrder::create([
            'po_number' => 'PO-2024-005', 'vendor_id' => $vendors->skip(5)->first()->id ?? 1,
            'order_date' => '2024-03-01', 'delivery_date' => '2024-03-05',
            'status' => 'pending', 'subtotal' => 28000, 'tax' => 2240, 'total' => 30240,
            'notes' => 'Diesel fuel for March operations',
        ]);
        PurchaseOrderItem::insert([
            ['purchase_order_id' => $po5->id, 'description' => 'Diesel (Liter)', 'unit' => 'Litre', 'quantity' => 5000, 'unit_price' => 5.60, 'total' => 28000, 'account_id' => $fuelAcct->id ?? null],
        ]);

        // PO 6: Safety gear from Safety One
        $po6 = PurchaseOrder::create([
            'po_number' => 'PO-2024-006', 'vendor_id' => $vendors->skip(4)->first()->id ?? 1,
            'order_date' => '2024-03-10', 'delivery_date' => '2024-03-18',
            'status' => 'pending', 'subtotal' => 12500, 'tax' => 1000, 'total' => 13500,
            'notes' => 'Monthly safety gear replenishment',
        ]);
        PurchaseOrderItem::insert([
            ['purchase_order_id' => $po6->id, 'description' => 'Safety Helmets', 'unit' => 'Unit', 'quantity' => 50, 'unit_price' => 35, 'total' => 1750, 'account_id' => $expenseAcct->id ?? null],
            ['purchase_order_id' => $po6->id, 'description' => 'Safety Vests', 'unit' => 'Unit', 'quantity' => 50, 'unit_price' => 25, 'total' => 1250, 'account_id' => $expenseAcct->id ?? null],
            ['purchase_order_id' => $po6->id, 'description' => 'Safety Boots', 'unit' => 'Pair', 'quantity' => 30, 'unit_price' => 120, 'total' => 3600, 'account_id' => $expenseAcct->id ?? null],
            ['purchase_order_id' => $po6->id, 'description' => 'Traffic Cones', 'unit' => 'Unit', 'quantity' => 100, 'unit_price' => 28, 'total' => 2800, 'account_id' => $expenseAcct->id ?? null],
            ['purchase_order_id' => $po6->id, 'description' => 'Warning Signs', 'unit' => 'Unit', 'quantity' => 20, 'unit_price' => 155, 'total' => 3100, 'account_id' => $expenseAcct->id ?? null],
        ]);

        // PO 7: Road marking materials from Permanent Mark
        $po7 = PurchaseOrder::create([
            'po_number' => 'PO-2024-007', 'vendor_id' => $vendors->skip(12)->first()->id ?? 1,
            'order_date' => '2024-03-15', 'delivery_date' => '2024-03-25',
            'status' => 'pending', 'subtotal' => 45000, 'tax' => 3600, 'total' => 48600,
            'notes' => 'Thermoplastic paint and glass beads',
        ]);
        PurchaseOrderItem::insert([
            ['purchase_order_id' => $po7->id, 'description' => 'Thermoplastic Paint Powder', 'unit' => 'Bag', 'quantity' => 200, 'unit_price' => 180, 'total' => 36000, 'account_id' => $inventoryAcct->id ?? null],
            ['purchase_order_id' => $po7->id, 'description' => 'Glass Beads', 'unit' => 'Bag', 'quantity' => 100, 'unit_price' => 90, 'total' => 9000, 'account_id' => $inventoryAcct->id ?? null],
        ]);

        // PO 8: Office supplies from Popular
        $po8 = PurchaseOrder::create([
            'po_number' => 'PO-2024-008', 'vendor_id' => $vendors->skip(6)->first()->id ?? 1,
            'order_date' => '2024-04-01', 'delivery_date' => '2024-04-05',
            'status' => 'pending', 'subtotal' => 3500, 'tax' => 280, 'total' => 3780,
            'notes' => 'Office stationery',
        ]);
        PurchaseOrderItem::insert([
            ['purchase_order_id' => $po8->id, 'description' => 'A4 Paper (Box)', 'unit' => 'Box', 'quantity' => 10, 'unit_price' => 120, 'total' => 1200, 'account_id' => $expenseAcct->id ?? null],
            ['purchase_order_id' => $po8->id, 'description' => 'Printer Toner', 'unit' => 'Unit', 'quantity' => 5, 'unit_price' => 180, 'total' => 900, 'account_id' => $expenseAcct->id ?? null],
            ['purchase_order_id' => $po8->id, 'description' => 'General Stationery', 'unit' => 'Lot', 'quantity' => 1, 'unit_price' => 1400, 'total' => 1400, 'account_id' => $expenseAcct->id ?? null],
        ]);

        // Bills (from received POs)
        $bill1 = Bill::create([
            'bill_number' => 'BILL-2024-001', 'vendor_id' => $vendors->first()->id ?? 1, 'purchase_order_id' => $po1->id,
            'vendor_bill_no' => 'INV-EURO-001', 'bill_date' => '2024-01-20', 'due_date' => '2024-02-19',
            'status' => 'paid', 'subtotal' => 120000, 'tax' => 9600, 'total' => 129600, 'paid_amount' => 129600, 'balance' => 0,
            'notes' => 'Payment made via bank transfer',
        ]);
        BillItem::insert([
            ['bill_id' => $bill1->id, 'description' => 'ACW14 Asphalt', 'unit' => 'Ton', 'quantity' => 500, 'unit_price' => 240, 'total' => 120000, 'account_id' => $inventoryAcct->id ?? null],
        ]);
        $payableAcct = $coa->where('code', '2101')->first();
        $cashAcct = $coa->where('code', '1102')->first();
        BillPayment::create([
            'bill_id' => $bill1->id, 'amount' => 129600, 'payment_date' => '2024-02-05',
            'debit_account_id' => $payableAcct->id ?? null, 'credit_account_id' => $cashAcct->id ?? null,
            'payment_reference' => 'TT-EURO-001',
        ]);

        $bill2 = Bill::create([
            'bill_number' => 'BILL-2024-002', 'vendor_id' => $vendors->skip(1)->first()->id ?? 1, 'purchase_order_id' => $po2->id,
            'vendor_bill_no' => 'INV-PULAI-001', 'bill_date' => '2024-01-25', 'due_date' => '2024-02-24',
            'status' => 'paid', 'subtotal' => 45000, 'tax' => 3600, 'total' => 48600, 'paid_amount' => 48600, 'balance' => 0,
            'notes' => 'Payment made via online transfer',
        ]);
        BillItem::insert([
            ['bill_id' => $bill2->id, 'description' => '20mm Aggregates', 'unit' => 'Ton', 'quantity' => 200, 'unit_price' => 120, 'total' => 24000, 'account_id' => $inventoryAcct->id ?? null],
            ['bill_id' => $bill2->id, 'description' => '14mm Aggregates', 'unit' => 'Ton', 'quantity' => 150, 'unit_price' => 140, 'total' => 21000, 'account_id' => $inventoryAcct->id ?? null],
        ]);
        BillPayment::create([
            'bill_id' => $bill2->id, 'amount' => 48600, 'payment_date' => '2024-02-15',
            'debit_account_id' => $payableAcct->id ?? null, 'credit_account_id' => $cashAcct->id ?? null,
            'payment_reference' => 'TT-PULAI-001',
        ]);

        // Inventory Stock-In Transactions (for received items)
        $inventoryItems = InventoryItem::all();
        $thermoItem = $inventoryItems->where('sku', 'RM-TP-001')->first();
        $glassItem = $inventoryItems->where('sku', 'RM-GB-001')->first();

        if ($thermoItem) {
            InventoryTransaction::create([
                'item_id' => $thermoItem->id, 'type' => 'in', 'qty' => 100,
                'unit_cost' => 50, 'total_cost' => 5000,
                'reference_type' => 'purchase_order', 'reference_id' => $po1->id,
                'notes' => 'Stock in from PO-2024-001',
            ]);
        }
        if ($glassItem) {
            InventoryTransaction::create([
                'item_id' => $glassItem->id, 'type' => 'in', 'qty' => 50,
                'unit_cost' => 65, 'total_cost' => 3250,
                'reference_type' => 'purchase_order', 'reference_id' => $po2->id,
                'notes' => 'Stock in from PO-2024-002',
            ]);
        }

        // Additional inventory stock in for other items
        foreach ($inventoryItems as $item) {
            if ($item->sku !== 'RM-TP-001' && $item->sku !== 'RM-GB-001') {
                InventoryTransaction::create([
                    'item_id' => $item->id, 'type' => 'in', 'qty' => $item->stock_qty * 0.5,
                    'unit_cost' => $item->unit_cost, 'total_cost' => $item->unit_cost * $item->stock_qty * 0.5,
                    'reference_type' => 'manual', 'reference_id' => null,
                    'notes' => 'Initial stock loading',
                ]);
            }
        }
    }
}
