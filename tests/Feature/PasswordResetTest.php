<?php

use App\Models\StaffProfile;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

use function Pest\Laravel\postJson;

beforeEach(function () {
    config(['app.frontend_url' => 'https://erp.test']);

    $this->admin = User::where('email', 'superadmin@azamventures.com')->first();
    $this->adminHeaders = ['Authorization' => 'Bearer '.$this->admin->createToken('test')->plainTextToken];
});

function resetUser(): User
{
    return User::factory()->create();
}

function resetTokenFor(User $user): string
{
    return Password::broker()->createToken($user);
}

describe('Admin triggered reset link', function () {

    it('brands the email header with the app logo', function () {
        $user = resetUser();
        $html = (new ResetPasswordNotification('token-123'))->toMail($user)->render();

        expect(str_contains($html, '/mail-logo.png'))->toBeTrue();
        expect(str_contains($html, 'laravel.com'))->toBeFalse();
        expect(str_contains($html, '#ca2316'))->toBeTrue();
    });

    it('sends a reset link instead of a generated password', function () {
        Notification::fake();
        $user = resetUser();

        $res = postJson('/api/v1/users/'.$user->id.'/reset-password', [], $this->adminHeaders);

        $res->assertOk()->assertJsonPath('email', $user->email);
        expect($res->json('generated_password'))->toBeNull();
        Notification::assertSentTo($user, ResetPasswordNotification::class);
        expect(DB::table('password_reset_tokens')->where('email', $user->email)->exists())->toBeTrue();
    });

    it('links to the frontend reset page', function () {
        Notification::fake();
        $user = resetUser();

        postJson('/api/v1/users/'.$user->id.'/reset-password', [], $this->adminHeaders)->assertOk();

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, 'https://erp.test/reset-password?')
                && str_contains($url, 'email='.urlencode($user->email));
        });
    });

    it('sends a reset link for staff reset', function () {
        Notification::fake();
        $user = resetUser();
        $staff = StaffProfile::factory()->create(['email' => $user->email]);

        postJson('/api/v1/staff/'.$staff->id.'/reset-password', [], $this->adminHeaders)
            ->assertOk()
            ->assertJsonPath('email', $user->email);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    });

    it('throttles repeated reset requests', function () {
        Notification::fake();
        $user = resetUser();

        postJson('/api/v1/users/'.$user->id.'/reset-password', [], $this->adminHeaders)->assertOk();
        postJson('/api/v1/users/'.$user->id.'/reset-password', [], $this->adminHeaders)->assertStatus(429);
    });

    it('does not expose a public forgot password endpoint', function () {
        postJson('/api/v1/auth/forgot-password', ['email' => 'someone@example.com'])
            ->assertNotFound();
    });
});

describe('Reset password with token', function () {

    it('updates the password, revokes sessions and clears lockout', function () {
        $user = resetUser();
        $user->update(['login_attempts' => 5, 'locked_until' => now()->addMinutes(15)]);
        $user->createToken('old-session');
        $token = resetTokenFor($user);

        postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertOk()->assertJsonPath('message', 'Password reset successfully');

        $user->refresh();
        expect(password_verify('NewPass123', $user->password))->toBeTrue();
        expect($user->login_attempts)->toBe(0);
        expect($user->locked_until)->toBeNull();
        expect($user->tokens()->count())->toBe(0);
        expect(DB::table('password_reset_tokens')->where('email', $user->email)->exists())->toBeFalse();
    });

    it('rejects an invalid token', function () {
        $user = resetUser();

        postJson('/api/v1/auth/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertStatus(422);

        expect(password_verify('NewPass123', $user->fresh()->password))->toBeFalse();
    });

    it('rejects a weak password', function () {
        $user = resetUser();
        $token = resetTokenFor($user);

        postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertStatus(422);
    });

    it('rejects mismatched confirmation', function () {
        $user = resetUser();
        $token = resetTokenFor($user);

        postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass123',
            'password_confirmation' => 'Different123',
        ])->assertStatus(422);
    });
});
