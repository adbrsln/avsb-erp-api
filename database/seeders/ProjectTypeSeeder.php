<?php

namespace Database\Seeders;

use App\Models\PhaseTemplate;
use App\Models\ProjectType;

class ProjectTypeSeeder
{
    public function run(): void
    {
        $paving = ProjectType::firstOrCreate(
            ['code' => 'paving'],
            ['name' => 'Paving', 'code' => 'paving', 'color' => '#3b82f6', 'sort_order' => 1]
        );

        $milling = ProjectType::firstOrCreate(
            ['code' => 'milling'],
            ['name' => 'Milling', 'code' => 'milling', 'color' => '#f59e0b', 'sort_order' => 2]
        );

        $roadMarking = ProjectType::firstOrCreate(
            ['code' => 'road_marking'],
            ['name' => 'Road Marking', 'code' => 'road_marking', 'color' => '#10b981', 'sort_order' => 3]
        );

        $drainage = ProjectType::firstOrCreate(
            ['code' => 'drainage'],
            ['name' => 'Drainage', 'code' => 'drainage', 'color' => '#8b5cf6', 'sort_order' => 4]
        );

        $structure = ProjectType::firstOrCreate(
            ['code' => 'structure'],
            ['name' => 'Structure', 'code' => 'structure', 'color' => '#ef4444', 'sort_order' => 5]
        );

        $general = ProjectType::firstOrCreate(
            ['code' => 'general'],
            ['name' => 'General', 'code' => 'general', 'color' => '#6b7280', 'sort_order' => 6]
        );

        $millPave = ProjectType::firstOrCreate(
            ['code' => 'mill_pave'],
            ['name' => 'Mill & Pave', 'code' => 'mill_pave', 'color' => '#dc2626', 'sort_order' => 7]
        );

        // Paving: lab_report before coring_test (order 3→4)
        $paving->phaseTemplates()->syncWithoutDetaching($this->mapOrder([
            'site_visit' => 1, 'start_date' => 2, 'lab_report' => 3,
            'coring_test' => 4, 'road_marking' => 5, 'jms' => 6,
            'lks' => 7, 'tnb' => 8, 'se' => 9,
        ]));

        // Milling: coring_test before lab_report (order 3→4)
        $milling->phaseTemplates()->syncWithoutDetaching($this->mapOrder([
            'site_visit' => 1, 'start_date' => 2, 'coring_test' => 3,
            'lab_report' => 4, 'road_marking' => 5, 'jms' => 6,
            'lks' => 7, 'tnb' => 8, 'se' => 9,
        ]));

        // Mill & Pave: same as Milling ordering
        $millPave->phaseTemplates()->syncWithoutDetaching($this->mapOrder([
            'site_visit' => 1, 'start_date' => 2, 'coring_test' => 3,
            'lab_report' => 4, 'road_marking' => 5, 'jms' => 6,
            'lks' => 7, 'tnb' => 8, 'se' => 9,
        ]));

        // Road Marking: 6-phase workflow
        $roadMarking->phaseTemplates()->syncWithoutDetaching($this->mapOrder([
            'site_visit' => 1, 'cleaning' => 2, 'marking' => 3,
            'glass_beads' => 4, 'qc' => 5, 'send_invoice' => 6,
        ]));

        // Simple types: only site_visit + send_invoice
        foreach ([$drainage, $structure, $general] as $type) {
            $type->phaseTemplates()->syncWithoutDetaching($this->mapOrder([
                'site_visit' => 1, 'send_invoice' => 2,
            ]));
        }
    }

    private function mapOrder(array $mapping): array
    {
        $result = [];
        foreach ($mapping as $code => $order) {
            $template = PhaseTemplate::where('code', $code)->first();
            if ($template) {
                $result[$template->id] = ['sort_order' => $order];
            }
        }

        return $result;
    }
}
