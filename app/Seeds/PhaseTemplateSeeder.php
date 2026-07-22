<?php

namespace App\Seeds;

use App\Models\PhaseTemplate;

class PhaseTemplateSeeder
{
    public function run(): void
    {
        $templates = [
            ['name' => 'Site Visit', 'code' => 'site_visit', 'order' => 1],
            ['name' => 'Start Date', 'code' => 'start_date', 'order' => 2],
            ['name' => 'Coring Test', 'code' => 'coring_test', 'order' => 3],
            ['name' => 'Lab Report', 'code' => 'lab_report', 'order' => 4],
            ['name' => 'Road Marking', 'code' => 'road_marking', 'order' => 5],
            ['name' => 'JMS', 'code' => 'jms', 'order' => 6],
            ['name' => 'LKS', 'code' => 'lks', 'order' => 7],
            ['name' => 'TNB', 'code' => 'tnb', 'order' => 8],
            ['name' => 'SE', 'code' => 'se', 'order' => 9],
            ['name' => 'Road Cleaning', 'code' => 'cleaning', 'order' => 11],
            ['name' => 'Paint Marking', 'code' => 'marking', 'order' => 12],
            ['name' => 'Glass Beads', 'code' => 'glass_beads', 'order' => 13],
            ['name' => 'Quality Check', 'code' => 'qc', 'order' => 14],
            ['name' => 'Send Invoice', 'code' => 'send_invoice', 'order' => 10],
        ];

        foreach ($templates as $t) {
            PhaseTemplate::firstOrCreate(
                ['code' => $t['code']],
                $t
            );
        }
    }
}
