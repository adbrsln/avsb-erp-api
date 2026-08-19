<?php

use App\Models\ActivityLog;
use App\Models\User;

use function Pest\Laravel\postJson;

describe('login audit', function () {
    beforeEach(function () {
        $this->user = User::create([
            'name' => 'Audit Tester',
            'email' => 'audit@example.com',
            'password' => password_hash('Secret123', PASSWORD_BCRYPT),
        ]);
    });

    it('logs a login event on successful login', function () {
        postJson('/api/v1/auth/login', [
            'email' => 'audit@example.com',
            'password' => 'Secret123',
        ])->assertStatus(200);

        $log = ActivityLog::where('log_name', 'auth')
            ->where('event', 'login')
            ->first();

        expect($log)->not->toBeNull()
            ->and($log->causer_id)->toBe((string) $this->user->id)
            ->and($log->description)->toContain('audit@example.com')
            ->and($log->properties['ip'])->toBe('127.0.0.1')
            ->and($log->properties)->toHaveKey('user_agent');
    });

    it('logs a failed login event for a known user', function () {
        postJson('/api/v1/auth/login', [
            'email' => 'audit@example.com',
            'password' => 'wrongpass',
        ])->assertStatus(401);

        $log = ActivityLog::where('log_name', 'auth')
            ->where('event', 'login_failed')
            ->where('causer_id', (string) $this->user->id)
            ->first();

        expect($log)->not->toBeNull()
            ->and($log->properties['attempts'])->toBe(1);
    });

    it('logs a failed login event for an unknown email', function () {
        postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ])->assertStatus(401);

        $log = ActivityLog::where('log_name', 'auth')
            ->where('event', 'login_failed')
            ->whereNull('causer_id')
            ->first();

        expect($log)->not->toBeNull()
            ->and($log->properties['email'])->toBe('nobody@example.com');
    });

    it('logs an account_locked event after 5 failed attempts', function () {
        for ($i = 0; $i < 5; $i++) {
            postJson('/api/v1/auth/login', [
                'email' => 'audit@example.com',
                'password' => 'wrongpass',
            ])->assertStatus(401);
        }

        $locked = ActivityLog::where('log_name', 'auth')
            ->where('event', 'account_locked')
            ->first();

        expect($locked)->not->toBeNull()
            ->and($locked->properties['attempts'])->toBe(5);

        // Subsequent attempt while locked → blocked event
        postJson('/api/v1/auth/login', [
            'email' => 'audit@example.com',
            'password' => 'Secret123',
        ])->assertStatus(423);

        $blocked = ActivityLog::where('log_name', 'auth')
            ->where('event', 'login_blocked')
            ->first();

        expect($blocked)->not->toBeNull()
            ->and($blocked->properties['reason'])->toBe('locked');
    });

    it('returns login events from the audit-logs endpoint', function () {
        postJson('/api/v1/auth/login', [
            'email' => 'audit@example.com',
            'password' => 'Secret123',
        ])->assertStatus(200);

        $user = User::where('email', 'superadmin@azamventures.com')->first();
        $token = $user->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/audit-logs?log_name=auth&event=login', ['Authorization' => 'Bearer '.$token])
            ->assertStatus(200)
            ->assertJsonPath('data.0.event', 'login')
            ->assertJsonPath('data.0.log_name', 'auth');
    });
});
