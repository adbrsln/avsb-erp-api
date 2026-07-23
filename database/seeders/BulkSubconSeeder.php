<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectSubcontractor;
use App\Models\SelfBilledInvoice;
use App\Models\StaffProfile;
use App\Models\Subcontractor;
use App\Models\SubcontractorClaim;
use App\Models\SubcontractorClaimDocument;
use App\Services\NumberingService;

class BulkSubconSeeder
{
    public function run(): void
    {
        $projects = Project::all();
        $subs = Subcontractor::all();
        $pm = StaffProfile::first();

        if ($projects->isEmpty() || $subs->isEmpty()) {
            return;
        }

        $numService = new NumberingService;

        for ($i = 0; $i < 50; $i++) {
            $project = $projects->random();
            $sub = $subs->random();
            $value = fake()->randomFloat(2, 20000, 500000);
            $retentionPct = rand(3, 10);

            ProjectSubcontractor::create([
                'project_id' => $project->id,
                'subcontractor_id' => $sub->id,
                'scope_of_work' => 'Subcontracted works for '.$project->name,
                'contract_value' => $value,
                'retention_pct' => $retentionPct,
                'retention_amount' => round($value * $retentionPct / 100, 2),
                'dlp_end_date' => fake()->dateTimeBetween('2025-01-01', '2026-12-31'),
                'status' => fake()->randomElement(['active', 'pending', 'completed']),
                'assigned_by' => $pm?->id ?? 1,
            ]);
        }

        $assignments = ProjectSubcontractor::all();
        for ($i = 0; $i < 50 && $i < $assignments->count(); $i++) {
            $ps = $assignments->random();
            $progress = rand(30, 95);
            $claimed = round($ps->contract_value * $progress / 100, 2);
            $status = fake()->randomElement(['submitted', 'verified', 'approved', 'paid']);

            $sc = SubcontractorClaim::create([
                'project_subcontractor_id' => $ps->id,
                'claim_number' => 'SC-'.uniqid(),
                'claim_date' => fake()->dateTimeBetween('2024-01-01', '2024-12-31'),
                'period_start' => fake()->dateTimeBetween('2024-01-01', '2024-06-30'),
                'period_end' => fake()->dateTimeBetween('2024-07-01', '2024-12-31'),
                'work_done_pct' => $progress,
                'cumulative_pct' => min($progress + rand(5, 10), 100),
                'claimed_amount' => $claimed,
                'retention_deducted' => round($claimed * ($ps->retention_pct ?? 5) / 100, 2),
                'net_payable' => round($claimed * (1 - ($ps->retention_pct ?? 5) / 100), 2),
                'previous_paid' => round($claimed * 0.3, 2),
                'current_due' => round($claimed * 0.6, 2),
                'status' => $status,
                'submitted_by' => $pm?->id ?? 1,
                'submitted_at' => fake()->dateTimeBetween('2024-01-15', '2024-11-30'),
                'verified_by' => $status !== 'submitted' ? $pm?->id ?? 1 : null,
                'verified_at' => $status !== 'submitted' ? fake()->dateTimeBetween('2024-02-01', '2024-12-15') : null,
                'approved_by' => in_array($status, ['approved', 'paid']) ? 1 : null,
                'approved_at' => in_array($status, ['approved', 'paid']) ? fake()->dateTimeBetween('2024-02-15', '2024-12-20') : null,
                'paid_at' => $status === 'paid' ? fake()->dateTimeBetween('2024-03-01', '2024-12-31') : null,
                'payment_reference' => $status === 'paid' ? 'TT-BULK-'.str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT) : null,
            ]);

            if (rand(0, 1)) {
                SubcontractorClaimDocument::create([
                    'subcontractor_claim_id' => $sc->id,
                    'uploaded_by' => $pm?->id ?? 1,
                    'original_filename' => 'claim_doc_'.($i + 1).'.pdf',
                    'stored_filename' => 'subcon_bulk/'.$sc->id.'/doc.pdf',
                    'file_path' => 'subcon_bulk/'.$sc->id.'/doc.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => rand(100000, 500000),
                    'notes' => 'Supporting document',
                ]);
            }
        }

        $allSubs = Subcontractor::all();
        for ($i = 0; $i < 50; $i++) {
            $project = $projects->random();
            $sub = $allSubs->random();
            $subtotal = fake()->randomFloat(2, 10000, 100000);
            $sst = round($subtotal * 0.08, 2);
            $retention = round($subtotal * 0.05, 2);

            SelfBilledInvoice::create([
                'invoice_number' => $numService->generate('self_billed'),
                'supplier_id' => $sub->id,
                'project_id' => $project->id,
                'date' => fake()->dateTimeBetween('2024-01-01', '2024-12-31'),
                'due_date' => fake()->dateTimeBetween('2024-02-01', '2025-01-31'),
                'supply_date' => fake()->dateTimeBetween('2024-01-01', '2024-12-31'),
                'subtotal' => $subtotal,
                'sst' => $sst,
                'retention' => $retention,
                'total' => round($subtotal + $sst - $retention, 2),
                'items' => [['description' => 'Subcontracted works', 'quantity' => 1, 'unit' => 'Lot', 'unit_rate' => $subtotal, 'total' => $subtotal]],
                'status' => fake()->randomElement(['pending', 'approved', 'paid', 'submitted']),
                'created_by' => $pm?->id ?? 1,
            ]);
        }
    }
}
