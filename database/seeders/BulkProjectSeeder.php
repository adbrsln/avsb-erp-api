<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\StaffProfile;
use App\Models\Task;
use App\Services\NumberingService;
use Illuminate\Support\Facades\DB;

class BulkProjectSeeder
{
    public function run(): void
    {
        $staff = StaffProfile::all();
        $projectTypes = ProjectType::all();
        $statuses = ['active', 'planning', 'active', 'active', 'completed', 'paused', 'active'];
        $projectNames = [
            'TNB Ampang Cable', 'MRSM Gombak Road', 'Klang Valley Pothole', 'Jalan Ipoh Resurfacing',
            'KLCC Marking', 'Lebuhraya Utara Milling', 'Shah Alam Drainage', 'Putrajaya Maintenance',
            'Cyberjaya Road Marking', 'Seremban Highway Repair', 'Jalan Duta Upgrade', 'Puchong Link Road',
            'Setia Alam Paving', 'Bangi Phase 3', 'Kajang Milling Works', 'Nilai Road Marking',
            'Dengkil Drainage', 'Sepang Circuit Access', 'Putrajaya Boulevard', 'KLIA Highway Repair',
        ];
        $locations = ['Kuala Lumpur', 'Shah Alam', 'Johor Bahru', 'Penang', 'Ipoh', 'Seremban', 'Melaka', 'Kuantan', 'Kota Kinabalu', 'Kuching'];
        $phaseNames = ['Mobilization', 'Preparation', 'Execution', 'Quality Control', 'Handover'];
        $taskNames = ['Site preparation & setup', 'Resource mobilization', 'Main works execution', 'Quality inspection', 'Documentation', 'Safety compliance check', 'Material procurement', 'Progress reporting'];

        if ($staff->isEmpty()) {
            return;
        }

        $numService = new NumberingService;

        for ($i = 0; $i < 20; $i++) {
            $client = Client::inRandomOrder()->first() ?? Client::factory()->create();

            $project = Project::create([
                'project_code' => $numService->generate('project'),
                'name' => $projectNames[$i % count($projectNames)],
                'client' => $client->company_name,
                'client_id' => $client->id,
                'client_pic_id' => $client->pics()->first()?->id,
                'project_manager_id' => $staff->random()->id,
                'po_number' => 'PO-'.strtoupper(substr($client->company_name, 0, 4)).'-'.str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                'location' => $locations[array_rand($locations)],
                'status' => $statuses[array_rand($statuses)],
                'budget_amount' => fake()->randomFloat(2, 50000, 5000000),
                'start_date' => fake()->dateTimeBetween('2024-01-01', '2024-12-31')->format('Y-m-d'),
                'end_date' => fake()->dateTimeBetween('2024-06-01', '2025-12-31')->format('Y-m-d'),
            ]);

            if ($projectTypes->isNotEmpty()) {
                $project->projectTypes()->syncWithoutDetaching([$projectTypes->random()->id]);
            }

            $tasksAdded = 0;
            for ($p = 0; $p < 5; $p++) {
                $phaseStart = date('Y-m-d', strtotime($project->start_date.' +'.($p * 30).' days'));
                $phaseEnd = date('Y-m-d', strtotime($phaseStart.' +28 days'));

                $phase = Phase::create([
                    'project_id' => $project->id,
                    'name' => $phaseNames[$p],
                    'order' => $p + 1,
                    'status' => $p === 0 ? 'in_progress' : 'pending',
                    'start_date' => $phaseStart,
                    'end_date' => $phaseEnd,
                ]);

                for ($t = 0; $t < 3 && $tasksAdded < 150; $t++) {
                    Task::create([
                        'phase_id' => $phase->id,
                        'title' => $taskNames[array_rand($taskNames)],
                        'description' => $phase->name.' task for '.$project->name,
                        'status' => fake()->randomElement(['todo', 'running', 'completed', 'todo', 'todo']),
                        'priority' => fake()->randomElement(['low', 'medium', 'high', 'high']),
                        'start_date' => $phaseStart,
                        'end_date' => $phaseEnd,
                        'assigned_to' => $staff->random()->id,
                    ]);
                    $tasksAdded++;
                }
            }

            $assignedStaff = $staff->random(rand(2, 5));
            foreach ($assignedStaff as $s) {
                DB::table('project_staff_pics')->insert([
                    'project_id' => $project->id,
                    'staff_id' => $s->id,
                ]);
            }
        }
    }
}
