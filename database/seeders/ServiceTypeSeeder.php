<?php

namespace Database\Seeders;

use App\Models\ServiceType;

class ServiceTypeSeeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Asphalt Milling',
                'description' => 'Asphalt milling and removal services',
                'default_phase_templates' => [
                    ['name' => 'Site Preparation', 'order' => 1],
                    ['name' => 'Milling Operation', 'order' => 2],
                    ['name' => 'Site Clearance', 'order' => 3],
                ],
                'unit_rates' => [
                    ['name' => 'Mobilization', 'unit' => 'setup', 'rate' => 5000],
                    ['name' => 'Milling 40mm', 'unit' => 'Sqm', 'rate' => 22],
                    ['name' => 'Milling 80mm', 'unit' => 'Sqm', 'rate' => 38],
                    ['name' => 'Disposal', 'unit' => 'Trip', 'rate' => 350],
                ],
            ],
            [
                'name' => 'Asphalt Paving',
                'description' => 'Asphalt paving and laying services',
                'default_phase_templates' => [
                    ['name' => 'Surface Preparation', 'order' => 1],
                    ['name' => 'Paving', 'order' => 2],
                    ['name' => 'Compaction & Finish', 'order' => 3],
                ],
                'unit_rates' => [
                    ['name' => 'Prep', 'unit' => 'Sqm', 'rate' => 4.5],
                    ['name' => 'Tack Coat', 'unit' => 'Sqm', 'rate' => 3.2],
                    ['name' => 'ACW14 50mm', 'unit' => 'Sqm', 'rate' => 48],
                    ['name' => 'Rolling', 'unit' => 'Hour', 'rate' => 150],
                ],
            ],
            [
                'name' => 'Road Marking',
                'description' => 'Road marking and signage painting',
                'default_phase_templates' => [
                    ['name' => 'Surface Cleaning', 'order' => 1],
                    ['name' => 'Marking Application', 'order' => 2],
                    ['name' => 'Curing & Inspection', 'order' => 3],
                ],
                'unit_rates' => [
                    ['name' => 'Continuous Line', 'unit' => 'Meter', 'rate' => 7.5],
                    ['name' => 'Double Line', 'unit' => 'Meter', 'rate' => 14],
                    ['name' => 'Arrow', 'unit' => 'Unit', 'rate' => 95],
                    ['name' => 'Ped Crossing', 'unit' => 'Sqm', 'rate' => 45],
                ],
            ],
            [
                'name' => 'Crack Sealing',
                'description' => 'Crack sealing and joint repair services',
                'default_phase_templates' => [
                    ['name' => 'Inspection & Routing', 'order' => 1],
                    ['name' => 'Sealant Application', 'order' => 2],
                    ['name' => 'Cleanup', 'order' => 3],
                ],
                'unit_rates' => [
                    ['name' => 'Router Cutting', 'unit' => 'Meter', 'rate' => 8.5],
                    ['name' => 'Sealant Injection', 'unit' => 'Meter', 'rate' => 18.5],
                ],
            ],
        ];

        foreach ($types as $type) {
            ServiceType::create($type);
        }
    }
}
