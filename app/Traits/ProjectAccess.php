<?php

namespace App\Traits;

use App\Models\Phase;
use App\Models\Project;
use App\Models\StaffProfile;
use Psr\Http\Message\ServerRequestInterface;

trait ProjectAccess
{
    protected function getStaffFromRequest(ServerRequestInterface $request): ?StaffProfile
    {
        $user = $request->getAttribute('user');
        if (!$user) return null;
        $email = is_object($user) ? ($user->email ?? '') : ($user['email'] ?? '');
        if (empty($email)) return null;
        return StaffProfile::where('email', $email)->first();
    }

    protected function getUserRoles(ServerRequestInterface $request): array
    {
        $user = $request->getAttribute('user');
        return is_object($user) ? ($user->roles ?? []) : ($user['roles'] ?? []);
    }

    protected function isPmPlus(ServerRequestInterface $request): bool
    {
        return (bool) array_intersect($this->getUserRoles($request), ['admin', 'pm', 'super_admin']);
    }

    protected function isProjectMember(ServerRequestInterface $request, int $projectId): bool
    {
        if ($this->isPmPlus($request)) return true;

        $staff = $this->getStaffFromRequest($request);
        if (!$staff) return false;

        if (Project::find($projectId)?->staffPics()
            ->where('staff_id', $staff->id)
            ->exists()) return true;

        return Phase::where('project_id', $projectId)
            ->whereHas('tasks.staff', fn($q) => $q->where('staff_id', $staff->id))
            ->exists();
    }
}
