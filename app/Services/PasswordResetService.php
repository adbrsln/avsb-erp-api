<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Password;

class PasswordResetService
{
    public function sendLink(User $user): string
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            ActivityLogger::on($user)->inLog('auth')->log("Password reset link sent to {$user->email}", 'password_reset_requested');
        }

        return $status;
    }

    public function reset(array $credentials): string
    {
        return Password::reset($credentials, function (User $user, string $password): void {
            $user->forceFill([
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'login_attempts' => 0,
                'locked_until' => null,
            ])->save();

            $user->tokens()->delete();

            ActivityLogContext::setCauser($user);
            ActivityLogger::on($user)->inLog('auth')->log("Password reset for {$user->email}", 'password_reset');
        });
    }
}
