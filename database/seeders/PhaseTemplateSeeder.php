<?php

namespace Database\Seeders;

use App\Models\PhaseTemplate;

class PhaseTemplateSeeder
{
    public function run(): void
    {

        $templates = [
            ['name' => 'PO Confirmation', 'code' => 'po_confirmation', 'order' => 0],
            ['name' => 'Site Visit', 'code' => 'site_visit', 'order' => 1],
            ['name' => '⁠Mill and pave (Project Execution)', 'code' => 'project_execution', 'order' => 2],
            ['name' => 'Coring Test', 'code' => 'coring_test', 'order' => 3],
            ['name' => 'Lab Report', 'code' => 'lab_report', 'order' => 4],
            ['name' => 'Road Marking', 'code' => 'road_marking', 'order' => 5],
            ['name' => 'Joint Measurement Sheet (JMS)', 'code' => 'jms', 'order' => 6],
            ['name' => 'Laporan Kerja Siap (LKS)', 'code' => 'lks', 'order' => 7],
            ['name' => 'Service Entry (SE)', 'code' => 'se', 'order' => 8],
            ['name' => 'Invoice Submission', 'code' => 'invoice_submission', 'order' => 9],
            ['name' => 'Payment Settlement (30 days)', 'code' => 'payment_settlement', 'order' => 10],
            ['name' => 'Road Cleaning', 'code' => 'cleaning', 'order' => 11],
            ['name' => 'Paint Marking', 'code' => 'marking', 'order' => 12],
            ['name' => 'Glass Beads', 'code' => 'glass_beads', 'order' => 13],
            ['name' => 'Quality Check', 'code' => 'qc', 'order' => 14],
        ];

        foreach ($templates as $t) {
            PhaseTemplate::firstOrCreate(
                ['code' => $t['code']],
                $t
            );
        }
    }
}
