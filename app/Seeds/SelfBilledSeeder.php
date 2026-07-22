<?php

namespace App\Seeds;

use App\Models\Project;
use App\Models\SelfBilledInvoice;
use App\Models\StaffProfile;
use App\Models\Subcontractor;

class SelfBilledSeeder
{
    public function run(): void
    {
        if (SelfBilledInvoice::count() > 0) {
            return;
        }

        $projects = Project::all();
        $subs = Subcontractor::all();
        $staff = StaffProfile::all();

        if ($projects->isEmpty() || $subs->isEmpty()) {
            return;
        }

        $admin = $staff->first();
        $pm = $staff->where('email', 'ahmadi@example.com')->first() ?? $admin;

        $invoices = [
            ['sub_idx' => 0, 'proj_idx' => 0, 'date' => '2024-03-15', 'subtotal' => 85000],
            ['sub_idx' => 1, 'proj_idx' => 0, 'date' => '2024-04-01', 'subtotal' => 22000],
            ['sub_idx' => 2, 'proj_idx' => 1, 'date' => '2024-04-15', 'subtotal' => 48000],
            ['sub_idx' => 3, 'proj_idx' => 1, 'date' => '2024-05-10', 'subtotal' => 15000],
            ['sub_idx' => 4, 'proj_idx' => 2, 'date' => '2024-05-20', 'subtotal' => 32000],
        ];

        foreach ($invoices as $i => $inv) {
            $sub = $subs->values()->get($inv['sub_idx'] % $subs->count());
            $project = $projects->values()->get($inv['proj_idx'] % $projects->count());
            if (! $sub || ! $project) {
                continue;
            }

            $sst = round($inv['subtotal'] * 0.08, 2);
            $retention = round($inv['subtotal'] * 0.05, 2);
            $total = round($inv['subtotal'] + $sst - $retention, 2);

            SelfBilledInvoice::create([
                'invoice_number' => 'SI-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'supplier_id' => $sub->id,
                'project_id' => $project->id,
                'date' => $inv['date'],
                'due_date' => date('Y-m-d', strtotime($inv['date'].' +30 days')),
                'supply_date' => $inv['date'],
                'subtotal' => $inv['subtotal'],
                'sst' => $sst,
                'retention' => $retention,
                'total' => $total,
                'items' => [
                    ['description' => 'Subcontracted works - '.$sub->company_name, 'quantity' => 1, 'unit' => 'Lot', 'unit_rate' => $inv['subtotal'], 'total' => $inv['subtotal']],
                    ['description' => 'Service Tax (8%)', 'quantity' => 1, 'unit' => 'Lot', 'unit_rate' => $sst, 'total' => $sst],
                ],
                'status' => $i < 2 ? 'paid' : ($i < 4 ? 'approved' : 'pending'),
                'approved_by' => $i < 4 ? $admin->id ?? 1 : null,
                'approved_at' => $i < 4 ? date('Y-m-d', strtotime($inv['date'].' +3 days')).' 10:00:00' : null,
                'created_by' => $pm->id ?? 1,
            ]);
        }
    }
}
