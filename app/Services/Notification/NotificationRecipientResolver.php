<?php

namespace App\Services\Notification;

use App\Models\Phase;
use App\Models\Project;
use App\Models\StaffProfile;
use App\Models\Subcontractor;
use App\Models\User;

class NotificationRecipientResolver
{
    public static function getByRole(array $roles): array
    {
        $userIds = User::whereHas('roles', function ($q) use ($roles) {
            $q->whereIn('role', $roles);
        })->pluck('email');

        return StaffProfile::whereIn('email', $userIds)
            ->where('is_active', true)
            ->get(['email', 'name'])
            ->map(fn ($s) => ['email' => $s->email, 'name' => $s->name])
            ->toArray();
    }

    public static function getAdmin(): array
    {
        return self::getByRole(['admin']);
    }

    public static function getFinance(): array
    {
        return self::getByRole(['finance', 'admin']);
    }

    public static function getHr(): array
    {
        return self::getByRole(['hr', 'admin']);
    }

    public static function getPm(): array
    {
        return self::getByRole(['pm', 'admin']);
    }

    public static function getApprovers(string $domain): array
    {
        return match ($domain) {
            'leave' => self::getHr(),
            'claim' => self::getFinance(),
            'timecard' => self::getPm(),
            'subcon' => self::getPm(),
            'project-claim' => self::getByRole(['pm', 'admin', 'finance', 'super_admin']),
            'self-billed' => self::getFinance(),
            'po' => self::getByRole(['admin', 'finance']),
            default => self::getAdmin(),
        };
    }

    public static function getProjectStaffPics(int $projectId): array
    {
        $project = Project::with('staffPics')->find($projectId);
        if (! $project) {
            return [];
        }

        return $project->staffPics
            ->filter(fn ($s) => $s->is_active)
            ->map(fn ($s) => ['email' => $s->email, 'name' => $s->name])
            ->toArray();
    }

    public static function getPhaseStaff(int $phaseId): array
    {
        return StaffProfile::whereHas('phases', fn ($q) => $q->where('phase_id', $phaseId))
            ->where('is_active', true)
            ->get(['email', 'name'])
            ->map(fn ($s) => ['email' => $s->email, 'name' => $s->name])
            ->toArray();
    }

    public static function getTaskStaff(int $taskId): array
    {
        return StaffProfile::whereHas('tasks', fn ($q) => $q->where('task_id', $taskId))
            ->where('is_active', true)
            ->get(['email', 'name'])
            ->map(fn ($s) => ['email' => $s->email, 'name' => $s->name])
            ->toArray();
    }

    public static function getSubcontractorEmail(int $subcontractorId): ?array
    {
        $sub = Subcontractor::find($subcontractorId);
        if (! $sub || ! $sub->email) {
            return null;
        }

        return ['email' => $sub->email, 'name' => $sub->company_name ?? ''];
    }

    public static function getProjectParticipants(int $projectId): array
    {
        $pmRecipients = self::getByRole(['admin', 'pm', 'super_admin']);
        $pmEmails = array_column($pmRecipients, 'email');

        $picRecipients = self::getProjectStaffPics($projectId);
        $picEmails = array_column($picRecipients, 'email');

        $phaseStaff = StaffProfile::whereHas('phases', fn ($q) => $q->whereIn('phase_id',
            Phase::where('project_id', $projectId)->pluck('id')
        ))->where('is_active', true)->get(['email', 'name'])->toArray();

        $phaseIds = Phase::where('project_id', $projectId)->pluck('id');
        $taskStaff = [];
        if ($phaseIds->isNotEmpty()) {
            $taskStaff = StaffProfile::whereHas('tasks', fn ($q) => $q->whereIn('phase_id', $phaseIds))
                ->where('is_active', true)
                ->get(['email', 'name'])
                ->toArray();
        }

        $all = array_merge($pmRecipients, $picRecipients, $phaseStaff, $taskStaff);
        $seen = [];

        return array_values(array_filter($all, function ($r) use (&$seen) {
            $key = $r['email'] ?? '';
            if (! $key || isset($seen[$key])) {
                return false;
            }
            $seen[$key] = true;

            return true;
        }));
    }

    public static function staffToRecipient(StaffProfile $staff): array
    {
        return ['email' => $staff->email, 'name' => $staff->name];
    }
}
