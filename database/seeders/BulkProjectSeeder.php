<?php

namespace Database\Seeders;

use App\Helpers\MalaysianDataGenerator as G;
use App\Models\Client;
use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\StaffProfile;
use App\Models\Task;
use App\Services\NumberingService;
use Illuminate\Database\Capsule\Manager as Capsule;

class BulkProjectSeeder
{
    public function run(): void
    {
        $clients = Client::all();
        $staff = StaffProfile::all();
        $projectTypes = ProjectType::all();
        $statuses = ['active', 'planning', 'active', 'active', 'completed', 'paused', 'active'];

        if ($clients->isEmpty() || $staff->isEmpty()) {
            return;
        }

        // 20 projects with multiple phases and tasks
        for ($i = 0; $i < 20; $i++) {
            $client = $clients->random();
            $pmId = $staff->random()->id;

            $project = Project::create([
                'project_code' => (new NumberingService)->generate('project'),
                'name' => G::randomProjectName(),
                'client' => $client->company_name,
                'client_id' => $client->id,
                'client_pic_id' => $client->pics()->first()?->id,
                'project_manager_id' => $pmId,
                'service_type_id' => rand(1, 4),
                'po_number' => 'PO-'.strtoupper(substr($client->company_name, 0, 4)).'-'.str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                'location' => G::randomLocation(),
                'status' => $statuses[array_rand($statuses)],
                'budget_amount' => G::randomAmount(50000, 5000000),
                'start_date' => G::randomDate('2024-01-01', '2024-12-31'),
                'end_date' => G::randomDate('2024-06-01', '2025-12-31'),
            ]);

            // Link to project type
            if ($projectTypes->isNotEmpty()) {
                $project->projectTypes()->syncWithoutDetaching([$projectTypes->random()->id]);
            }

            // 5 phases per project
            $phaseNames = ['Mobilization', 'Preparation', 'Execution', 'Quality Control', 'Handover'];
            $tasksAdded = 0;
            for ($p = 0; $p < 5; $p++) {
                $phaseStart = date('Y-m-d', strtotime($project->start_date.' +'.($p * 30).' days'));
                $phaseEnd = date('Y-m-d', strtotime($phaseStart.' +28 days'));

                $phase = Phase::create([
                    'project_id' => $project->id,
                    'name' => $phaseNames[$p],
                    'order' => $p + 1,
                    'status' => $p === 0 ? 'in_progress' : ($p < 3 ? 'pending' : 'pending'),
                    'start_date' => $phaseStart,
                    'end_date' => $phaseEnd,
                ]);

                // 3 tasks per phase
                $taskNames = ['Site preparation & setup', 'Resource mobilization', 'Main works execution', 'Quality inspection', 'Documentation', 'Safety compliance check', 'Material procurement', 'Progress reporting'];
                for ($t = 0; $t < 3 && $tasksAdded < 150; $t++) {
                    $taskStaff = $staff->random()->id;
                    Task::create([
                        'phase_id' => $phase->id,
                        'title' => $taskNames[array_rand($taskNames)],
                        'description' => $phase->name.' task for '.$project->name,
                        'status' => ['todo', 'running', 'completed', 'todo', 'todo'][array_rand([0, 1, 2, 3, 4])],
                        'priority' => ['low', 'medium', 'high', 'high'][array_rand([0, 1, 2, 3])],
                        'start_date' => $phaseStart,
                        'end_date' => $phaseEnd,
                        'assigned_to' => $taskStaff,
                    ]);
                    $tasksAdded++;
                }
            }

            // Assign staff to project
            $assignedStaff = $staff->random(rand(2, 5));
            foreach ($assignedStaff as $s) {
                Capsule::table('project_staff_pics')->insert([
                    'project_id' => $project->id,
                    'staff_id' => $s->id,
                ]);
            }
        }
    }
}
