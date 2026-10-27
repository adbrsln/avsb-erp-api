<?php

use App\Models\ActivityLog;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->admin = User::where('email', 'superadmin@azamventures.com')->first();
    $this->adminHeaders = ['Authorization' => 'Bearer '.$this->admin->createToken('test')->plainTextToken];
});

function makeUser(array $roles = ['staff']): User
{
    $user = User::factory()->create();
    $user->syncRoles($roles);

    return $user;
}

function logAuth(User $user, string $event = 'login', array $properties = []): ActivityLog
{
    return ActivityLog::create([
        'log_name' => 'auth',
        'description' => "{$event} for {$user->email}",
        'subject_type' => User::class,
        'subject_id' => (string) $user->id,
        'causer_type' => User::class,
        'causer_id' => (string) $user->id,
        'properties' => $properties,
        'event' => $event,
    ]);
}

describe('User list security metadata', function () {

    it('exposes is_locked and last_login_at', function () {
        $user = makeUser();
        logAuth($user);

        $res = getJson('/api/v1/users?search='.urlencode($user->email), $this->adminHeaders);

        $res->assertOk();
        $row = collect($res->json('data'))->firstWhere('id', $user->id);

        expect($row)->not->toBeNull();
        expect($row['is_locked'])->toBeFalse();
        expect($row['last_login_at'])->not->toBeNull();
    });

    it('marks a locked account', function () {
        $user = makeUser();
        $user->update(['login_attempts' => 5, 'locked_until' => now()->addMinutes(15)]);

        $res = getJson('/api/v1/users?search='.urlencode($user->email), $this->adminHeaders);

        $row = collect($res->json('data'))->firstWhere('id', $user->id);
        expect($row['is_locked'])->toBeTrue();
    });

    it('filters locked accounts only', function () {
        $locked = makeUser();
        $locked->update(['locked_until' => now()->addMinutes(15)]);
        $active = makeUser();

        $res = getJson('/api/v1/users?status=locked', $this->adminHeaders);

        $res->assertOk();
        $ids = collect($res->json('data'))->pluck('id');
        expect($ids)->toContain($locked->id);
        expect($ids)->not->toContain($active->id);
    });

    it('returns account totals', function () {
        $total = User::count();
        $locked = makeUser();
        $locked->update(['locked_until' => now()->addMinutes(15)]);

        getJson('/api/v1/users/summary', $this->adminHeaders)
            ->assertOk()
            ->assertJsonPath('total', $total + 1)
            ->assertJsonPath('locked', 1);
    });
});

describe('User security detail', function () {

    it('returns recent auth events and last login', function () {
        $user = makeUser();
        logAuth($user, 'login');
        logAuth($user, 'login_failed', ['attempts' => 1]);

        $res = getJson('/api/v1/users/'.$user->id, $this->adminHeaders);

        $res->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonCount(2, 'auth_events');
        expect($res->json('last_login_at'))->not->toBeNull();
    });

    it('unlocks a locked account and clears failed attempts', function () {
        $user = makeUser();
        $user->update(['login_attempts' => 5, 'locked_until' => now()->addMinutes(15)]);

        $res = postJson('/api/v1/users/'.$user->id.'/unlock', [], $this->adminHeaders);

        $res->assertOk()->assertJsonPath('is_locked', false);
        $user->refresh();
        expect($user->login_attempts)->toBe(0);
        expect($user->locked_until)->toBeNull();
        expect(ActivityLog::where('event', 'account_unlocked')->where('subject_id', (string) $user->id)->exists())->toBeTrue();
    });

    it('is idempotent when the account is not locked', function () {
        $user = makeUser();

        postJson('/api/v1/users/'.$user->id.'/unlock', [], $this->adminHeaders)
            ->assertOk()
            ->assertJsonPath('is_locked', false);

        expect(ActivityLog::where('event', 'account_unlocked')->exists())->toBeFalse();
    });

    it('forbids non super admins from unlocking', function () {
        $staff = makeUser();
        $headers = ['Authorization' => 'Bearer '.$staff->createToken('test')->plainTextToken];

        postJson('/api/v1/users/'.$this->admin->id.'/unlock', [], $headers)
            ->assertStatus(403);
    });
});
