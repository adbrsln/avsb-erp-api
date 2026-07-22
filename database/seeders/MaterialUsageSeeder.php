<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\Project;
use App\Models\ProjectMaterialUsage;
use App\Models\StaffProfile;

class MaterialUsageSeeder
{
    public function run(): void
    {
        if (ProjectMaterialUsage::count() > 0) {
            return;
        }

        $projects = Project::all();
        $items = InventoryItem::all();
        $staff = StaffProfile::all();

        if ($projects->isEmpty() || $items->isEmpty()) {
            return;
        }

        $uploader = $staff->first()->id ?? 1;

        $usageRecords = [
            ['item_sku' => 'RM-TP-001', 'qty' => 50, 'unit_cost' => 50],
            ['item_sku' => 'RM-GB-001', 'qty' => 20, 'unit_cost' => 65],
            ['item_sku' => 'RM-PT-001', 'qty' => 10, 'unit_cost' => 180],
            ['item_sku' => 'RM-PT-002', 'qty' => 5, 'unit_cost' => 190],
            ['item_sku' => 'RM-TH-001', 'qty' => 25, 'unit_cost' => 12],
            ['item_sku' => 'RM-PR-001', 'qty' => 10, 'unit_cost' => 25],
        ];

        foreach ($projects as $p) {
            $numRecords = rand(2, 4);
            for ($i = 0; $i < $numRecords; $i++) {
                $rec = $usageRecords[array_rand($usageRecords)];
                $item = $items->where('sku', $rec['item_sku'])->first();
                if (! $item) {
                    continue;
                }

                ProjectMaterialUsage::create([
                    'project_id' => $p->id,
                    'item_id' => $item->id,
                    'qty' => $rec['qty'] + rand(-5, 10),
                    'unit_cost' => $rec['unit_cost'],
                    'total_cost' => ($rec['qty'] + rand(-5, 10)) * $rec['unit_cost'],
                    'notes' => 'Material issued for project works',
                    'created_by' => $uploader,
                ]);
            }
        }
    }
}
