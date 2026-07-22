<?php

namespace App\Traits;

use App\Models\StaffProfile;
use Illuminate\Http\Request;

trait StaffHelper
{
    protected function getStaffId(Request $request): ?int
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $email = $user->email ?? '';
        if (empty($email)) {
            return null;
        }

        $staff = StaffProfile::where('email', $email)->first();

        return $staff ? (int) $staff->id : null;
    }
}
