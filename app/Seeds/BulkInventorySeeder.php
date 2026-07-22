<?php

namespace App\Seeds;

use App\Helpers\MalaysianDataGenerator as G;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Database\Capsule\Manager as Capsule;

class BulkInventorySeeder
{
    public function run(): void
    {
        Capsule::connection()->statement('SET FOREIGN_KEY_CHECKS = 0');
        InventoryItem::truncate();
        InventoryTransaction::truncate();
        Capsule::connection()->statement('SET FOREIGN_KEY_CHECKS = 1');

        $categories = [
            'Asphalt & Bitumen' => ['ACW14 Asphalt', 'ACB28 Asphalt', 'Bitumen 60/70', 'Bitumen 80/100', 'Tack Coat', 'Prime Coat'],
            'Aggregates' => ['20mm Aggregate', '14mm Aggregate', '10mm Aggregate', 'Crusher Run', 'Quarry Dust', 'River Sand', 'Granite Chippings'],
            'Road Marking' => ['Thermoplastic Paint', 'Cold Paint White', 'Cold Paint Yellow', 'Glass Beads', 'Primer', 'Thinner'],
            'Safety' => ['Safety Helmet', 'Safety Vest', 'Safety Boots', 'Traffic Cone', 'Warning Sign', 'Barrier Tape', 'First Aid Kit'],
            'Fuel & Lubricants' => ['Diesel', 'Petrol', 'Hydraulic Oil', 'Engine Oil', 'Grease', 'Coolant'],
            'Concrete' => ['Ready Mix Concrete G25', 'Ready Mix Concrete G30', 'Cement', 'Steel Rebar 10mm', 'Steel Rebar 12mm', 'Wire Mesh', 'Concrete Block'],
            'Drainage' => ['Drainage Pipe 300mm', 'Drainage Pipe 450mm', 'Drainage Pipe 600mm', 'Culvert 1.2m', 'Culvert 1.5m', 'Gully Cover', 'Manhole Cover'],
            'General' => ['Timber Formwork', 'Plywood Sheet', 'Nails', 'Binding Wire', 'PVC Pipe', 'Cable Tie', 'Measuring Tape'],
        ];

        $items = [];
        $idx = 0;
        foreach ($categories as $cat => $products) {
            foreach ($products as $product) {
                $items[] = [
                    'sku' => 'SKU-'.strtoupper(substr($cat, 0, 3)).'-'.str_pad($idx + 1, 3, '0', STR_PAD_LEFT),
                    'name' => $product,
                    'category' => $cat,
                    'unit' => ['Ton', 'Bag', 'Litre', 'Unit', 'Pair', 'Drum', 'Sheet', 'Meter'][array_rand([0, 1, 2, 3, 4, 5, 6, 7])],
                    'stock_qty' => G::randomAmount(10, 1000, 0),
                    'unit_cost' => G::randomAmount(5, 500),
                    'reorder_level' => rand(10, 100),
                    'status' => 'active',
                ];
                $idx++;
            }
        }

        foreach (array_chunk($items, 50) as $chunk) {
            InventoryItem::insert($chunk);
        }

        // Stock-in transactions for each item
        $transactions = [];
        foreach (InventoryItem::all() as $item) {
            $numTx = rand(1, 3);
            for ($t = 0; $t < $numTx; $t++) {
                $qty = G::randomAmount(10, 200, 0);
                $transactions[] = [
                    'item_id' => $item->id,
                    'type' => 'in',
                    'qty' => $qty,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $qty * $item->unit_cost,
                    'reference_type' => 'manual',
                    'notes' => 'Initial stock loading',
                ];
            }
        }

        foreach (array_chunk($transactions, 50) as $chunk) {
            InventoryTransaction::insert($chunk);
        }
    }
}
