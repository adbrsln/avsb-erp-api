<?php

namespace Database\Seeders;

use App\Models\InventoryItem;

class InventoryItemSeeder
{
    public function run(): void
    {
        $items = [
            ['sku' => 'RM-TP-001', 'name' => 'Thermoplastic Paint Powder', 'category' => 'Road Marking', 'unit' => 'Bag', 'stock_qty' => 500, 'unit_cost' => 50.00, 'reorder_level' => 50],
            ['sku' => 'RM-GB-001', 'name' => 'Glass Beads', 'category' => 'Road Marking', 'unit' => 'Bag', 'stock_qty' => 200, 'unit_cost' => 65.00, 'reorder_level' => 20],
            ['sku' => 'RM-PT-001', 'name' => 'Premixed Road Paint (White)', 'category' => 'Road Marking', 'unit' => 'Drum', 'stock_qty' => 50, 'unit_cost' => 180.00, 'reorder_level' => 10],
            ['sku' => 'RM-PT-002', 'name' => 'Premixed Road Paint (Yellow)', 'category' => 'Road Marking', 'unit' => 'Drum', 'stock_qty' => 30, 'unit_cost' => 190.00, 'reorder_level' => 5],
            ['sku' => 'RM-TH-001', 'name' => 'Thinner', 'category' => 'Road Marking', 'unit' => 'Litre', 'stock_qty' => 100, 'unit_cost' => 12.00, 'reorder_level' => 20],
            ['sku' => 'RM-PR-001', 'name' => 'Primer', 'category' => 'Road Marking', 'unit' => 'Litre', 'stock_qty' => 50, 'unit_cost' => 25.00, 'reorder_level' => 10],
        ];

        foreach ($items as $item) {
            InventoryItem::firstOrCreate(
                ['sku' => $item['sku']],
                $item
            );
        }
    }
}
