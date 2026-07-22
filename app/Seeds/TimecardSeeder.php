<?php

namespace App\Seeds;

use App\Models\Project;
use App\Models\StaffProfile;
use App\Models\Timecard;

class TimecardSeeder
{
    public function run(): void
    {
        if (Timecard::count() > 10) {
            return;
        }

        $staff = StaffProfile::all();
        $projects = Project::all();

        if ($staff->isEmpty() || $projects->isEmpty()) {
            return;
        }

        $descriptions = [
            'Site preparation works',
            'Paving operations',
            'Milling machine operation',
            'Quality inspection',
            'Material handling',
            'Safety briefing and toolbox talk',
            'Equipment maintenance',
            'Survey and measurement',
            'Compaction works',
            'Road marking application',
        ];

        $statuses = ['approved', 'approved', 'approved', 'pending', 'approved', 'rejected'];

        for ($i = 0; $i < 20; $i++) {
            $s = $staff->random();
            $p = $projects->random();
            $date = date('Y-m-d', strtotime('2024-0'.rand(3, 6).'-'.rand(1, 28)));

            Timecard::create([
                'staff_id' => $s->id,
                'project_id' => $p->id,
                'date' => $date,
                'hours_worked' => round(rand(60, 110) / 10, 1),
                'description' => $descriptions[array_rand($descriptions)],
                'status' => $statuses[array_rand($statuses)],
            ]);
        }
    }
}
