<?php

namespace App\Seeds;

use App\Helpers\MalaysianDataGenerator as G;
use App\Models\Project;
use App\Models\ProjectSubcontractor;
use App\Models\SelfBilledInvoice;
use App\Models\StaffProfile;
use App\Models\Subcontractor;
use App\Models\SubcontractorClaim;
use App\Models\SubcontractorClaimDocument;

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

        // 50 Project→Subcontractor assignments
        for ($i = 0; $i < 50; $i++) {
            $project = $projects->random();
            $sub = $subs->random();
            $value = G::randomAmount(20000, 500000);
            $retentionPct = rand(3, 10);

            ProjectSubcontractor::create([
                'project_id' => $project->id,
                'subcontractor_id' => $sub->id,
                'scope_of_work' => 'Subcontracted works for '.$project->name,
                'contract_value' => $value,
                'retention_pct' => $retentionPct,
                'retention_amount' => round($value * $retentionPct / 100, 2),
                'dlp_end_date' => G::randomDate('2025-01-01', '2026-12-31'),
                'status' => ['active', 'pending', 'completed'][array_rand([0, 1, 2])],
                'assigned_by' => $pm?->id ?? 1,
            ]);
        }

        // 50 Subcontractor claims
        $assignments = ProjectSubcontractor::all();
        for ($i = 0; $i < 50 && $i < $assignments->count(); $i++) {
            $ps = $assignments->random();
            $progress = rand(30, 95);
            $claimed = round($ps->contract_value * $progress / 100, 2);
            $status = ['submitted', 'verified', 'approved', 'paid'][array_rand([0, 1, 2, 3])];

            $sc = SubcontractorClaim::create([
                'project_subcontractor_id' => $ps->id,
                'claim_number' => 'SC-BULK-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'claim_date' => G::randomDate('2024-01-01', '2024-12-31'),
                'period_start' => G::randomDate('2024-01-01', '2024-06-30'),
                'period_end' => G::randomDate('2024-07-01', '2024-12-31'),
                'work_done_pct' => $progress,
                'cumulative_pct' => min($progress + rand(5, 10), 100),
                'claimed_amount' => $claimed,
                'retention_deducted' => round($claimed * ($ps->retention_pct ?? 5) / 100, 2),
                'net_payable' => round($claimed * (1 - ($ps->retention_pct ?? 5) / 100), 2),
                'previous_paid' => round($claimed * 0.3, 2),
                'current_due' => round($claimed * 0.6, 2),
                'status' => $status,
                'submitted_by' => $pm?->id ?? 1,
                'submitted_at' => G::randomDate('2024-01-15', '2024-11-30').' 10:00:00',
                'verified_by' => $status !== 'submitted' ? $pm?->id ?? 1 : null,
                'verified_at' => $status !== 'submitted' ? G::randomDate('2024-02-01', '2024-12-15').' 14:00:00' : null,
                'approved_by' => in_array($status, ['approved', 'paid']) ? 1 : null,
                'approved_at' => in_array($status, ['approved', 'paid']) ? G::randomDate('2024-02-15', '2024-12-20').' 16:00:00' : null,
                'paid_at' => $status === 'paid' ? G::randomDate('2024-03-01', '2024-12-31').' 10:00:00' : null,
                'payment_reference' => $status === 'paid' ? 'TT-BULK-'.str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT) : null,
            ]);

            // Doc for 50% of claims
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

        // 50 Self-billed invoices
        $allSubs = Subcontractor::all();
        for ($i = 0; $i < 50; $i++) {
            $project = $projects->random();
            $sub = $allSubs->random();
            $subtotal = G::randomAmount(10000, 100000);
            $sst = round($subtotal * 0.08, 2);
            $retention = round($subtotal * 0.05, 2);

            SelfBilledInvoice::create([
                'invoice_number' => 'SI-BULK-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'supplier_id' => $sub->id,
                'project_id' => $project->id,
                'date' => G::randomDate('2024-01-01', '2024-12-31'),
                'due_date' => G::randomDate('2024-02-01', '2025-01-31'),
                'supply_date' => G::randomDate('2024-01-01', '2024-12-31'),
                'subtotal' => $subtotal,
                'sst' => $sst,
                'retention' => $retention,
                'total' => $subtotal + $sst - $retention,
                'items' => [['description' => 'Subcontracted works', 'quantity' => 1, 'unit' => 'Lot', 'unit_rate' => $subtotal, 'total' => $subtotal]],
                'status' => ['pending', 'approved', 'paid', 'submitted'][array_rand([0, 1, 2, 3])],
                'created_by' => $pm?->id ?? 1,
            ]);
        }
    }
}
