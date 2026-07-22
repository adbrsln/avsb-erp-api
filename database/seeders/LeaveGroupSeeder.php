<?php

namespace Database\Seeders;

use App\Models\LeaveGroup;
use App\Models\LeaveGroupEntitlement;

class LeaveGroupSeeder
{
    public function run(): void
    {
        $groups = [
            [
                'name' => 'Malaysian Standard - Female',
                'description' => 'Standard leave entitlements based on Malaysian Employment Act',
                'entitlements' => [
                    ['type' => 'annual', 'label' => 'Annual Leave', 'days_entitled' => 14, 'sort_order' => 1],
                    ['type' => 'medical', 'label' => 'Medical Leave', 'days_entitled' => 14, 'sort_order' => 2],
                    ['type' => 'maternity', 'label' => 'Maternity Leave', 'days_entitled' => 98, 'sort_order' => 3],
                    ['type' => 'marriage', 'label' => 'Marriage Leave', 'days_entitled' => 3, 'sort_order' => 5],
                    ['type' => 'compassionate', 'label' => 'Compassionate Leave', 'days_entitled' => 3, 'sort_order' => 6],
                    ['type' => 'emergency', 'label' => 'Emergency Leave', 'days_entitled' => 3, 'sort_order' => 7],
                    ['type' => 'unpaid', 'label' => 'Unpaid Leave', 'days_entitled' => 0, 'sort_order' => 8],
                ],
            ],
            [
                'name' => 'Malaysian Standard Male',
                'description' => '',
                'entitlements' => [
                    ['type' => 'annual', 'label' => 'Annual Leave', 'days_entitled' => 14, 'sort_order' => 1],
                    ['type' => 'medical', 'label' => 'Medical Leave', 'days_entitled' => 14, 'sort_order' => 2],
                    ['type' => 'paternity', 'label' => 'Paternity', 'days_entitled' => 7, 'sort_order' => 3],
                    ['type' => 'marriage', 'label' => 'Marriage', 'days_entitled' => 3, 'sort_order' => 4],
                    ['type' => 'compassionate', 'label' => 'Compassionate', 'days_entitled' => 3, 'sort_order' => 5],
                    ['type' => 'emergency', 'label' => 'Emergency', 'days_entitled' => 3, 'sort_order' => 6],
                    ['type' => 'unpaid', 'label' => 'Unpaid', 'days_entitled' => 0, 'sort_order' => 7],
                ],
            ],
        ];

        foreach ($groups as $g) {
            $group = LeaveGroup::firstOrCreate(
                ['name' => $g['name']],
                ['description' => $g['description']]
            );
            foreach ($g['entitlements'] as $e) {
                LeaveGroupEntitlement::firstOrCreate(
                    ['leave_group_id' => $group->id, 'type' => $e['type']],
                    $e
                );
            }
        }
    }
}
