<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectSubcontractor;
use App\Models\StaffProfile;
use App\Models\Subcontractor;
use App\Models\SubcontractorClaim;
use App\Models\SubcontractorClaimDocument;

class SubconDataSeeder
{
    public function run(): void
    {
        if (ProjectSubcontractor::count() > 0) {
            return;
        }

        $projects = Project::all();
        $subs = Subcontractor::all();
        $staff = StaffProfile::all();
        $admin = $staff->first();
        $pm = $staff->where('email', 'ahmadi@example.com')->first() ?? $admin;

        if ($projects->isEmpty() || $subs->isEmpty()) {
            return;
        }

        $assignments = [
            ['proj_idx' => 0, 'sub_idx' => 0, 'scope' => 'Asphalt paving and compaction works', 'value' => 180000],
            ['proj_idx' => 0, 'sub_idx' => 1, 'scope' => 'Road marking and line painting', 'value' => 45000],
            ['proj_idx' => 1, 'sub_idx' => 2, 'scope' => 'Milling works 40mm depth', 'value' => 95000],
            ['proj_idx' => 1, 'sub_idx' => 3, 'scope' => 'Debris removal and disposal', 'value' => 32000],
            ['proj_idx' => 2, 'sub_idx' => 4, 'scope' => 'Thermoplastic road marking application', 'value' => 55000],
            ['proj_idx' => 0, 'sub_idx' => 2, 'scope' => 'Crack sealing and joint repair', 'value' => 28000],
            ['proj_idx' => 2, 'sub_idx' => 0, 'scope' => 'Site preparation and cleaning', 'value' => 22000],
            ['proj_idx' => 1, 'sub_idx' => 4, 'scope' => 'Concrete works for drainage', 'value' => 42000],
        ];

        foreach ($assignments as $idx => $a) {
            $project = $projects->values()->get($a['proj_idx'] % $projects->count());
            $sub = $subs->values()->get($a['sub_idx'] % $subs->count());
            if (! $project || ! $sub) {
                continue;
            }

            $retentionPct = rand(3, 10);
            $retentionAmount = round($a['value'] * $retentionPct / 100, 2);

            $ps = ProjectSubcontractor::create([
                'project_id' => $project->id,
                'subcontractor_id' => $sub->id,
                'scope_of_work' => $a['scope'],
                'contract_value' => $a['value'],
                'retention_pct' => $retentionPct,
                'retention_amount' => $retentionAmount,
                'dlp_end_date' => '2025-06-30',
                'cc_date' => '2024-12-31',
                'status' => $idx < 4 ? 'active' : 'pending',
                'assigned_by' => $pm->id ?? 1,
            ]);

            // Create 1-2 claims per active assignment
            if ($idx < 4) {
                for ($c = 1; $c <= 2; $c++) {
                    $claimStatus = $c === 1 ? 'paid' : 'submitted';
                    $workDone = $c === 1 ? rand(40, 60) : rand(70, 95);
                    $cumulative = $c === 1 ? $workDone : $workDone + rand(30, 40);
                    $cumulative = min($cumulative, 100);
                    $claimed = round($a['value'] * $workDone / 100, 2);
                    $retention = round($claimed * $retentionPct / 100, 2);

                    $sc = SubcontractorClaim::create([
                        'project_subcontractor_id' => $ps->id,
                        'claim_number' => 'SC-'.str_pad($idx + 1, 2, '0', STR_PAD_LEFT).'-'.str_pad($c, 2, '0', STR_PAD_LEFT),
                        'claim_date' => '2024-0'.($c * 2).'-15',
                        'period_start' => '2024-0'.(($c - 1) * 2 + 1).'-01',
                        'period_end' => '2024-0'.($c * 2).'-28',
                        'work_done_pct' => $workDone,
                        'cumulative_pct' => $cumulative,
                        'claimed_amount' => $claimed,
                        'retention_deducted' => $retention,
                        'net_payable' => round($claimed - $retention, 2),
                        'previous_paid' => $c === 1 ? 0 : round($a['value'] * 0.4, 2),
                        'current_due' => $c === 1 ? round($claimed - $retention, 2) : round($claimed - $retention - $a['value'] * 0.4, 2),
                        'status' => $claimStatus,
                        'submitted_by' => $pm->id ?? 1,
                        'submitted_at' => '2024-0'.($c * 2).'-20 10:00:00',
                        'verified_by' => $admin->id ?? 1,
                        'verified_at' => '2024-0'.($c * 2).'-22 14:00:00',
                        'approved_by' => $claimStatus === 'paid' ? $admin->id ?? 1 : null,
                        'approved_at' => $claimStatus === 'paid' ? '2024-0'.($c * 2).'-25 16:00:00' : null,
                        'paid_at' => $claimStatus === 'paid' ? '2024-0'.($c * 2 + 1).'-05 10:00:00' : null,
                        'payment_reference' => $claimStatus === 'paid' ? 'TT-SUB-'.str_pad($idx + 1, 2, '0', STR_PAD_LEFT) : null,
                    ]);

                    // Add a supporting document for the claim
                    SubcontractorClaimDocument::create([
                        'subcontractor_claim_id' => $sc->id,
                        'uploaded_by' => $pm->id ?? 1,
                        'original_filename' => "claim_{$idx}_{$c}_progress_report.pdf",
                        'stored_filename' => "subcon_claims/{$sc->id}/progress_report.pdf",
                        'file_path' => "subcon_claims/{$sc->id}/progress_report.pdf",
                        'mime_type' => 'application/pdf',
                        'file_size' => rand(200000, 800000),
                        'notes' => 'Progress report for claim '.$sc->claim_number,
                    ]);
                }
            }
        }
    }
}
