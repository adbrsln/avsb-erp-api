<?php

namespace Database\Seeders\Concerns;

use App\Models\Phase;
use App\Models\Project;
use App\Models\StaffProfile;

trait CreatesStandardPhases
{
    /**
     * Create project phases. With no custom list, creates the 9 standard phases
     * distributed evenly across the project timeline. Otherwise creates the given
     * phases (arrays or PhaseDefinition objects): entries with explicit start/end
     * dates use them as-is; entries without dates are spread evenly across the
     * timeline (list order preserved). Each entry: name (required), optional
     * start_date/end_date (Y-m-d), optional status (pending|in_progress|completed),
     * optional remarks (stored on description). Strictly validated.
     *
     * @param  array<int, array{name: string, start_date?: string, end_date?: string, status?: string, remarks?: string}>|PhaseDefinition|null  $phases
     */
    protected function createStandardPhases(Project $project, array|PhaseDefinition|null $phases = null, string $defaultStatus = 'pending'): void
    {
        $this->assertValidStatus($defaultStatus);

        $pm = StaffProfile::first();
        [$startTs, $endTs] = $this->resolvePhaseWindow($project);

        $defs = $phases ?? $this->defaultPhases();
        $normalized = [];
        foreach ($defs as $def) {
            $normalized[] = $def instanceof PhaseDefinition
                ? $this->normalizeObjectDefinition($def)
                : $this->normalizeArrayDefinition($def);
        }

        foreach ($normalized as $idx => $def) {
            $status = $def['status'] ?? $defaultStatus;
            $phaseStart = $def['start_date'] ?? null;
            $phaseEnd = $def['end_date'] ?? null;

            if ($phaseStart === null) {
                [$phaseStart, $phaseEnd] = $this->spreadDates($startTs, $endTs, $idx, count($normalized));
            } elseif ($phaseEnd === null) {
                $phaseEnd = date('Y-m-d', strtotime($phaseStart.' +1 day'));
            }

            Phase::create([
                'project_id' => $project->id,
                'name' => $def['name'],
                'order' => $idx + 1,
                'status' => $status,
                'start_date' => $phaseStart,
                'end_date' => $phaseEnd,
                'completed_at' => $status === 'completed'
                    ? date('Y-m-d H:i:s', strtotime(($phaseEnd ?? $phaseStart ?? date('Y-m-d')).' 17:00:00'))
                    : null,
                'completed_by' => $status === 'completed' ? $pm?->id : null,
                'description' => $def['remarks'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<int, array{name: string, start_date?: string, end_date?: string, status?: string, remarks?: string}>  $defs
     * @return array{name: string, start_date?: string, end_date?: string, status?: string, remarks?: string}
     */
    private function normalizeArrayDefinition(array $def): array
    {
        return (new ArrayPhaseDefinition($def))->toArray();
    }

    /** @return array{name: string, start_date?: string, end_date?: string, status?: string, remarks?: string} */
    private function normalizeObjectDefinition(PhaseDefinition $def): array
    {
        $normalized = ['name' => $def->name()];
        if ($def->startDate() !== null) {
            $normalized['start_date'] = $def->startDate();
        }
        if ($def->endDate() !== null) {
            $normalized['end_date'] = $def->endDate();
        }
        $normalized['status'] = $def->status();
        if ($def->remarks() !== null) {
            $normalized['remarks'] = $def->remarks();
        }

        return $this->normalizeArrayDefinition($normalized);
    }

    private function assertValidStatus(string $status): void
    {
        if (! in_array($status, ['pending', 'in_progress', 'completed'], true)) {
            throw new \RuntimeException("Invalid default status \"{$status}\" — must be pending, in_progress or completed");
        }
    }

    /** @return array<int, array{name: string}> */
    private function defaultPhases(): array
    {
        return [
            ['name' => 'Site Visit'],
            ['name' => 'Start Date'],
            ['name' => 'Coring Test'],
            ['name' => 'Lab Report'],
            ['name' => 'Road Marking'],
            ['name' => 'JMS'],
            ['name' => 'LKS'],
            ['name' => 'TNB'],
            ['name' => 'SE'],
        ];
    }

    /** @return array{int, int} unix timestamps for project start/end */
    private function resolvePhaseWindow(Project $project): array
    {
        $projStart = $project->start_date;
        $projEnd = $project->end_date;
        if (! $projEnd) {
            $projEnd = date('Y-m-d', strtotime(($projStart ?: date('Y-m-d')).' +30 days'));
        }

        return [strtotime($projStart ?: date('Y-m-d')), strtotime($projEnd)];
    }

    /** @return array{string, string} */
    private function spreadDates(int $startTs, int $endTs, int $index, int $count): array
    {
        $totalDays = max(1, (int) (($endTs - $startTs) / 86400));
        $segmentDays = $totalDays / max($count - 1, 1);
        $phaseStart = date('Y-m-d', (int) ($startTs + ($index * $segmentDays * 86400)));
        $phaseEnd = date('Y-m-d', (int) (strtotime($phaseStart) + max(86400, $segmentDays * 86400 * 0.3)));

        return [$phaseStart, $phaseEnd];
    }
}
