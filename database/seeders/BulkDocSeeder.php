<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\Project;
use App\Models\ProjectClaim;
use App\Models\ProjectClaimDocument;
use App\Models\ProjectDocument;
use App\Models\ProjectMaterialUsage;
use App\Models\StaffProfile;
use App\Services\NumberingService;

class BulkDocSeeder
{
    public function run(): void
    {
        $projects = Project::all();
        $staff = StaffProfile::all();

        if ($projects->isEmpty()) {
            return;
        }
        $uploader = $staff->first()?->id ?? 1;

        $numService = new NumberingService;

        $categories = ['photo', 'report', 'drawing', 'certificate', 'correspondence', 'photo', 'other'];
        $extensions = ['.jpg', '.pdf', '.pdf', '.pdf', '.pdf', '.png', '.xlsx'];
        $mimes = ['image/jpeg', 'application/pdf', 'application/pdf', 'application/pdf', 'application/pdf', 'image/png', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        $docNames = ['progress_photo', 'inspection_report', 'site_drawing', 'quality_cert', 'transmittal', 'site_image', 'measurement_sheet'];

        $docBatch = [];
        for ($i = 0; $i < 150; $i++) {
            $p = $projects->random();
            $catIdx = array_rand($categories);

            $docBatch[] = [
                'project_id' => $p->id,
                'uploaded_by' => $uploader,
                'original_filename' => $docNames[array_rand($docNames)].'_'.($i + 1).$extensions[$catIdx],
                'stored_filename' => 'bulk_docs/'.$p->id.'/'.($i + 1).$extensions[$catIdx],
                'file_path' => 'bulk_docs/'.$p->id.'/'.($i + 1).$extensions[$catIdx],
                'file_size' => rand(50000, 1500000),
                'mime_type' => $mimes[$catIdx],
                'category' => $categories[$catIdx],
                'notes' => 'Generated document for '.$p->name,
            ];
        }
        foreach (array_chunk($docBatch, 100) as $chunk) {
            ProjectDocument::insert($chunk);
        }

        for ($i = 0; $i < 50; $i++) {
            $p = $projects->random();
            $amount = fake()->randomFloat(2, 10000, 200000);
            $status = fake()->randomElement(['submitted', 'approved', 'paid', 'submitted']);

            $claim = ProjectClaim::create([
                'claim_number' => 'PC-'.uniqid(),
                'project_id' => $p->id,
                'title' => 'Progress Claim - Phase '.rand(1, 5),
                'description' => 'Progress claim for '.$p->name,
                'amount' => $amount,
                'status' => $status,
                'submitted_by' => $uploader,
                'approved_by' => $status !== 'submitted' ? 1 : null,
                'submitted_at' => fake()->dateTimeBetween('2024-01-01', '2024-12-31'),
                'approved_at' => $status !== 'submitted' ? fake()->dateTimeBetween('2024-02-01', '2024-12-31') : null,
                'items' => [['description' => 'Work done', 'amount' => $amount * 0.8], ['description' => 'Materials', 'amount' => $amount * 0.2]],
            ]);

            if (rand(0, 1)) {
                ProjectClaimDocument::create([
                    'project_claim_id' => $claim->id,
                    'uploaded_by' => $uploader,
                    'original_filename' => 'claim_support_'.($i + 1).'.pdf',
                    'stored_filename' => 'bulk_claim_docs/'.$claim->id.'/support.pdf',
                    'file_path' => 'bulk_claim_docs/'.$claim->id.'/support.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => rand(100000, 800000),
                    'notes' => 'Supporting document for '.$claim->claim_number,
                ]);
            }
        }

        $inventoryItemIds = InventoryItem::pluck('id')->toArray();
        if (! empty($inventoryItemIds)) {
            for ($i = 0; $i < 50; $i++) {
                $p = $projects->random();
                ProjectMaterialUsage::create([
                    'project_id' => $p->id,
                    'item_id' => $inventoryItemIds[array_rand($inventoryItemIds)],
                    'qty' => rand(5, 200),
                    'unit_cost' => fake()->randomFloat(2, 10, 300),
                    'total_cost' => 0,
                    'notes' => 'Material issued for project works',
                    'created_by' => $uploader,
                ]);
            }
        }
    }
}
