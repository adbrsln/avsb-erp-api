<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectClaim;
use App\Models\ProjectClaimDocument;
use App\Models\StaffProfile;

class ProjectClaimSeeder
{
    public function run(): void
    {
        if (ProjectClaim::count() > 0) {
            return;
        }

        $projects = Project::all();
        $staff = StaffProfile::all();
        $admin = $staff->first();
        $pm = $staff->where('email', 'ahmadi@example.com')->first() ?? $admin;

        if ($projects->isEmpty()) {
            return;
        }

        $claims = [
            ['project_idx' => 0, 'title' => 'Progress Claim 1', 'description' => 'First progress billing - mobilization and site setup', 'amount' => 50000],
            ['project_idx' => 0, 'title' => 'Progress Claim 2', 'description' => 'Second progress billing - paving works (60% complete)', 'amount' => 120000],
            ['project_idx' => 0, 'title' => 'Progress Claim 3', 'description' => 'Final progress billing - completion and handover', 'amount' => 85000],
            ['project_idx' => 1, 'title' => 'Progress Claim 1', 'description' => 'Mobilization and site preparation', 'amount' => 35000],
            ['project_idx' => 1, 'title' => 'Progress Claim 2', 'description' => 'Milling works (50% complete)', 'amount' => 75000],
            ['project_idx' => 2, 'title' => 'Progress Claim 1', 'description' => 'Site cleaning and preparation', 'amount' => 25000],
            ['project_idx' => min(1, $projects->count() - 1), 'title' => 'Variation Order 1', 'description' => 'Additional scope - drainage works', 'amount' => 18000],
        ];

        foreach ($claims as $idx => $c) {
            $project = $projects->values()->get($c['project_idx'] % $projects->count());
            $status = $idx % 3 === 0 ? 'approved' : ($idx % 3 === 1 ? 'submitted' : 'paid');

            $claim = ProjectClaim::create([
                'claim_number' => 'PC-'.str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                'project_id' => $project->id,
                'title' => $c['title'],
                'description' => $c['description'],
                'amount' => $c['amount'],
                'status' => $status,
                'submitted_by' => $pm->id ?? 1,
                'approved_by' => $status !== 'submitted' ? $admin->id ?? 1 : null,
                'submitted_at' => '2024-0'.($idx + 1).'-15 10:00:00',
                'approved_at' => $status !== 'submitted' ? '2024-0'.($idx + 1).'-20 14:00:00' : null,
                'items' => [
                    ['description' => $c['title'].' - Main Works', 'amount' => $c['amount'] * 0.7],
                    ['description' => $c['title'].' - Materials', 'amount' => $c['amount'] * 0.2],
                    ['description' => $c['title'].' - Preliminaries', 'amount' => $c['amount'] * 0.1],
                ],
            ]);

            if ($idx % 2 === 0) {
                ProjectClaimDocument::create([
                    'project_claim_id' => $claim->id,
                    'uploaded_by' => $pm->id ?? 1,
                    'original_filename' => 'claim_supporting_doc_'.($idx + 1).'.pdf',
                    'stored_filename' => 'claims/'.$claim->id.'/doc_'.($idx + 1).'.pdf',
                    'file_path' => 'claims/'.$claim->id.'/doc_'.($idx + 1).'.pdf',
                    'mime_type' => 'application/pdf', 'file_size' => rand(100000, 500000),
                    'notes' => 'Supporting document for '.$c['title'],
                ]);
            }
        }
    }
}
