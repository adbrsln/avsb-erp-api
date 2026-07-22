<?php

namespace App\Traits;

use App\Models\StaffProfile;
use Psr\Http\Message\ServerRequestInterface;

trait StaffHelper
{
    protected function getStaffId(ServerRequestInterface $request): ?int
    {
        $user = $request->getAttribute('user');
        if (! $user) {
            return null;
        }

        $email = is_object($user) ? ($user->email ?? '') : ($user['email'] ?? '');
        if (empty($email)) {
            return null;
        }

        $staff = StaffProfile::where('email', $email)->first();

        return $staff ? (int) $staff->id : null;
    }
}
