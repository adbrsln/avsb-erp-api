<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class BulkInventorySeeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        InventoryItem::truncate();
        InventoryTransaction::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        InventoryItem::factory()
            ->count(50)
            ->create()
            ->each(function (InventoryItem $item) {
                for ($t = 0; $t < rand(1, 3); $t++) {
                    $qty = fake()->numberBetween(10, 200);
                    InventoryTransaction::create([
                        'item_id' => $item->id,
                        'type' => 'in',
                        'qty' => $qty,
                        'unit_cost' => $item->unit_cost,
                        'total_cost' => $qty * $item->unit_cost,
                        'reference_type' => 'manual',
                        'notes' => 'Initial stock loading',
                    ]);
                }
            });
    }
}
