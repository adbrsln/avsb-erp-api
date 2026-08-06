<?php

namespace Database\Seeders\Concerns;

use App\Models\Phase;
use App\Models\Project;
use App\Models\StaffProfile;

trait CreatesStandardPhases
{
    /**
     * Create the 9 standard construction phases distributed evenly across the
     * project timeline, all marked completed (system override). Matches the
     * phase set used by MillPaveSeeder for TNB jobs.
     */
    protected function createStandardPhases(Project $project): void
    {
        $pm = StaffProfile::first();

        $projStart = $project->start_date;
        $projEnd = $project->end_date;
        if (! $projEnd) {
            $projEnd = date('Y-m-d', strtotime(($projStart ?: date('Y-m-d')).' +30 days'));
        }
        $startTs = strtotime($projStart ?: date('Y-m-d'));
        $endTs = strtotime($projEnd);
        $totalDays = max(1, (int) (($endTs - $startTs) / 86400));

        $allPhases = [
            ['code' => 'site_visit'],
            ['code' => 'start_date'],
            ['code' => 'coring_test'],
            ['code' => 'lab_report'],
            ['code' => 'road_marking'],
            ['code' => 'jms'],
            ['code' => 'lks'],
            ['code' => 'tnb'],
            ['code' => 'se'],
        ];

        $numPhases = count($allPhases);
        $segmentDays = $totalDays / max($numPhases - 1, 1);

        foreach ($allPhases as $idx => $p) {
            $order = $idx + 1;
            $phaseStart = date('Y-m-d', (int) ($startTs + ($idx * $segmentDays * 86400)));
            $phaseEnd = date('Y-m-d', (int) (strtotime($phaseStart) + max(86400, $segmentDays * 86400 * 0.3)));
            $name = in_array($p['code'], ['jms', 'lks', 'tnb', 'se'])
                ? strtoupper($p['code'])
                : ucwords(str_replace('_', ' ', $p['code']));

            Phase::create([
                'project_id' => $project->id,
                'name' => $name,
                'order' => $order,
                'status' => 'pending',
                'start_date' => $phaseStart,
                'end_date' => $phaseEnd,
                // 'completed_at' => $phaseEnd.' 17:00:00',
                // 'completed_by' => $pm?->id,
                // 'completion_remarks' => 'System override',
            ]);
        }
    }
}
