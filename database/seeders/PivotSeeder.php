<?php

namespace Database\Seeders;

use App\Models\Phase;
use App\Models\PhaseTemplate;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\StaffProfile;
use App\Models\Task;
use Illuminate\Database\Capsule\Manager as Capsule;

class PivotSeeder
{
    public function run(): void
    {
        $staff = StaffProfile::all();
        $projects = Project::all();
        $phases = Phase::all();
        $tasks = Task::all();
        $projectTypes = ProjectType::all();
        $phaseTemplates = PhaseTemplate::all();

        if ($staff->isEmpty()) {
            return;
        }

        $pmId = $staff->where('email', 'ahmadi@example.com')->first()?->id ?? $staff->first()->id;
        $adminId = $staff->first()->id;

        // project_staff_pics — assign PM and some staff to projects
        foreach ($projects as $p) {
            $assigned = [$pmId];
            // Pick additional staff (not PM)
            $others = $staff->where('id', '!=', $pmId)->pluck('id')->toArray();
            $extra = array_slice($others, 0, rand(1, min(3, count($others))));
            $assigned = array_merge($assigned, $extra);

            foreach (array_unique($assigned) as $sid) {
                Capsule::table('project_staff_pics')->updateOrInsert(
                    ['project_id' => $p->id, 'staff_id' => $sid],
                    ['project_id' => $p->id, 'staff_id' => $sid]
                );
            }
        }

        // phase_staff — assign staff to phases
        foreach ($phases as $ph) {
            $count = rand(1, 3);
            $randomStaff = $staff->random(min($count, $staff->count()));
            foreach ($randomStaff as $s) {
                Capsule::table('phase_staff')->updateOrInsert(
                    ['phase_id' => $ph->id, 'staff_id' => $s->id],
                    ['phase_id' => $ph->id, 'staff_id' => $s->id]
                );
            }
        }

        // task_staff — assign staff to tasks
        foreach ($tasks as $t) {
            $randomStaff = $staff->random(rand(1, 2));
            foreach ($randomStaff as $s) {
                Capsule::table('task_staff')->updateOrInsert(
                    ['task_id' => $t->id, 'staff_id' => $s->id],
                    ['task_id' => $t->id, 'staff_id' => $s->id]
                );
            }
        }

        // project_type_phase_template — ensure all project types have templates linked
        if ($projectTypes->isNotEmpty() && $phaseTemplates->isNotEmpty()) {
            foreach ($projectTypes as $pt) {
                $existing = Capsule::table('project_type_phase_template')
                    ->where('project_type_id', $pt->id)->count();
                if ($existing === 0) {
                    $templates = $phaseTemplates->random(min(4, $phaseTemplates->count()));
                    foreach ($templates as $idx => $tpl) {
                        Capsule::table('project_type_phase_template')->insert([
                            'project_type_id' => $pt->id,
                            'phase_template_id' => $tpl->id,
                            'sort_order' => $idx + 1,
                        ]);
                    }
                }
            }
        }
    }
}
