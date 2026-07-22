<?php

namespace App\Traits;

use App\Models\Phase;
use App\Models\Project;
use App\Models\StaffProfile;
use Illuminate\Http\Request;

trait ProjectAccess
{
    protected function getStaffFromRequest(Request $request): ?StaffProfile
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }
        $email = $user->email ?? '';
        if (empty($email)) {
            return null;
        }

        return StaffProfile::where('email', $email)->first();
    }

    protected function getUserRoles(Request $request): array
    {
        $user = $request->user();

        return $user ? $user->getRoleNames() : [];
    }

    protected function isPmPlus(Request $request): bool
    {
        return (bool) array_intersect($this->getUserRoles($request), ['admin', 'pm', 'super_admin']);
    }

    protected function isProjectMember(Request $request, int $projectId): bool
    {
        if ($this->isPmPlus($request)) {
            return true;
        }

        $staff = $this->getStaffFromRequest($request);
        if (! $staff) {
            return false;
        }

        if (Project::find($projectId)?->staffPics()
            ->where('staff_id', $staff->id)
            ->exists()) {
            return true;
        }

        return Phase::where('project_id', $projectId)
            ->whereHas('tasks.staff', fn ($q) => $q->where('staff_id', $staff->id))
            ->exists();
    }
}
