<?php

namespace App\Seeds;

use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\StaffProfile;
use App\Models\Task;

class ProjectDocSeeder
{
    public function run(): void
    {
        if (ProjectDocument::count() > 0) {
            return;
        }

        $projects = Project::all();
        $phases = Phase::all();
        $tasks = Task::all();
        $staff = StaffProfile::all();

        if ($projects->isEmpty()) {
            return;
        }
        $uploader = $staff->first()->id ?? 1;

        $docTemplates = [
            ['cat' => 'photo', 'mime' => 'image/jpeg', 'ext' => '.jpg', 'size' => 250000],
            ['cat' => 'photo', 'mime' => 'image/jpeg', 'ext' => '.jpg', 'size' => 180000],
            ['cat' => 'report', 'mime' => 'application/pdf', 'ext' => '.pdf', 'size' => 500000],
            ['cat' => 'drawing', 'mime' => 'application/pdf', 'ext' => '.pdf', 'size' => 1200000],
            ['cat' => 'certificate', 'mime' => 'application/pdf', 'ext' => '.pdf', 'size' => 350000],
            ['cat' => 'correspondence', 'mime' => 'application/pdf', 'ext' => '.pdf', 'size' => 150000],
            ['cat' => 'photo', 'mime' => 'image/png', 'ext' => '.png', 'size' => 300000],
            ['cat' => 'other', 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'ext' => '.xlsx', 'size' => 85000],
        ];

        $docNames = [
            'site_progress', 'inspection_form', 'material_delivery_note', 'safety_report',
            'daily_log', 'quality_checklist', 'as_built_drawing', 'transmittal_slip',
            'site_photo_aerial', 'testing_lab_report', 'calibration_cert', 'method_statement',
            'risk_assessment', 'toolbox_talk_record', 'change_order_request',
        ];

        $batch = [];
        $docIdx = 0;

        foreach ($projects as $p) {
            $projectPhases = $phases->where('project_id', $p->id);
            $projectTasks = $tasks->whereIn('phase_id', $projectPhases->pluck('id'));

            // 2 documents per project (general)
            for ($i = 0; $i < 2; $i++) {
                $tpl = $docTemplates[array_rand($docTemplates)];
                $name = $docNames[$docIdx % count($docNames)];

                $batch[] = [
                    'project_id' => $p->id,
                    'phase_id' => null,
                    'task_id' => null,
                    'uploaded_by' => $uploader,
                    'original_filename' => $name.'_'.$p->id.$tpl['ext'],
                    'stored_filename' => 'projects/'.$p->id.'/docs/'.$name.'_'.$docIdx.$tpl['ext'],
                    'file_path' => 'projects/'.$p->id.'/docs/'.$name.'_'.$docIdx.$tpl['ext'],
                    'file_size' => $tpl['size'] + rand(-50000, 50000),
                    'mime_type' => $tpl['mime'],
                    'category' => $tpl['cat'],
                    'notes' => 'Document for '.$p->name,
                ];
                $docIdx++;
            }

            // 1 document per phase
            foreach ($projectPhases as $ph) {
                $tpl = $docTemplates[array_rand($docTemplates)];
                $name = $docNames[$docIdx % count($docNames)];

                $batch[] = [
                    'project_id' => $p->id,
                    'phase_id' => $ph->id,
                    'task_id' => null,
                    'uploaded_by' => $uploader,
                    'original_filename' => $name.'_phase_'.$ph->id.$tpl['ext'],
                    'stored_filename' => 'projects/'.$p->id.'/phases/'.$ph->id.'/'.$name.$tpl['ext'],
                    'file_path' => 'projects/'.$p->id.'/phases/'.$ph->id.'/'.$name.$tpl['ext'],
                    'file_size' => $tpl['size'] + rand(-30000, 30000),
                    'mime_type' => $tpl['mime'],
                    'category' => $tpl['cat'],
                    'notes' => $ph->name.' - '.$tpl['cat'],
                ];
                $docIdx++;

                // 1 document per task
                $phaseTasks = $projectTasks->where('phase_id', $ph->id);
                foreach ($phaseTasks as $tsk) {
                    $tpl2 = $docTemplates[array_rand($docTemplates)];
                    $name2 = $docNames[$docIdx % count($docNames)];

                    $batch[] = [
                        'project_id' => $p->id,
                        'phase_id' => $ph->id,
                        'task_id' => $tsk->id,
                        'uploaded_by' => $uploader,
                        'original_filename' => $name2.'_task_'.$tsk->id.$tpl2['ext'],
                        'stored_filename' => 'projects/'.$p->id.'/tasks/'.$tsk->id.'/'.$name2.$tpl2['ext'],
                        'file_path' => 'projects/'.$p->id.'/tasks/'.$tsk->id.'/'.$name2.$tpl2['ext'],
                        'file_size' => $tpl2['size'] + rand(-20000, 20000),
                        'mime_type' => $tpl2['mime'],
                        'category' => $tpl2['cat'],
                        'notes' => $tsk->title.' - '.$tpl2['cat'],
                    ];
                    $docIdx++;
                }
            }
        }

        foreach (array_chunk($batch, 100) as $chunk) {
            ProjectDocument::insert($chunk);
        }
    }
}
