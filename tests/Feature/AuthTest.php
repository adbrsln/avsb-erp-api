<?php

use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

describe('Auth', function () {

    it('returns validation errors on login with empty data', function () {
        postJson('/api/v1/auth/login', [])
            ->assertStatus(422);
    });

    it('login fails with wrong credentials', function () {
        postJson('/api/v1/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrong',
        ])->assertStatus(401);
    });

    it('login succeeds with valid credentials', function () {
        postJson('/api/v1/auth/login', [
            'email' => 'superadmin@azamventures.com',
            'password' => 'secret',
        ])->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    });

    it('returns 401 for authenticated routes without token', function () {
        getJson('/api/v1/auth/me')->assertStatus(401);
        postJson('/api/v1/auth/logout')->assertStatus(401);
    });

    it('me endpoint returns user data', function () {
        $user = User::where('email', 'superadmin@azamventures.com')->first();
        $token = $user->createToken('test')->plainTextToken;

        getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$token])
            ->assertStatus(200)
            ->assertJsonStructure(['id', 'name', 'email']);
    });

    it('verify-password succeeds with correct password', function () {
        $user = User::where('email', 'superadmin@azamventures.com')->first();
        $token = $user->createToken('test')->plainTextToken;

        postJson('/api/v1/auth/verify-password', [
            'password' => 'secret',
        ], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(200)
            ->assertJson(['verified' => true]);
    });

    it('verify-password fails with wrong password', function () {
        $user = User::where('email', 'superadmin@azamventures.com')->first();
        $token = $user->createToken('test')->plainTextToken;

        postJson('/api/v1/auth/verify-password', [
            'password' => 'wrongpassword',
        ], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(422);
    });

    it('register creates new user', function () {
        $email = 'test_'.time().'@example.com';
        postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'TestPass123',
        ])->assertStatus(201)
            ->assertJsonStructure(['id', 'name', 'email']);
    });

    it('logout succeeds with valid token', function () {
        $user = User::where('email', 'superadmin@azamventures.com')->first();
        $token = $user->createToken('test')->plainTextToken;

        postJson('/api/v1/auth/logout', [], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(200);
    });

    it('rejects request with deleted token', function () {
        $user = User::where('email', 'superadmin@azamventures.com')->first();
        // Delete all existing tokens and create a fresh one
        $user->tokens()->delete();
        $token = $user->createToken('test')->plainTextToken;
        $user->tokens()->delete(); // Delete it immediately

        getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$token])
            ->assertStatus(401);
    });

    it('change-password requires old password', function () {
        $user = User::where('email', 'superadmin@azamventures.com')->first();
        $token = $user->createToken('test')->plainTextToken;

        $this->putJson('/api/v1/auth/change-password', [
            'password' => 'NewPass123',
        ], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(422);
    });

});
