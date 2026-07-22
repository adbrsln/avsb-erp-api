<?php

use App\Models\StaffProfile;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
    $this->staffId = StaffProfile::first()->id ?? 1;
});

describe('Staff', function () {

    it('returns 401 without token', function () {
        getJson('/api/v1/staff')->assertStatus(401);
    });

    it('lists all staff', function () {
        getJson('/api/v1/staff', $this->headers)
            ->assertStatus(200);
    });

    it('shows single staff member', function () {
        getJson('/api/v1/staff/'.$this->staffId, $this->headers)
            ->assertStatus(200);
    });

    it('returns my profile', function () {
        getJson('/api/v1/staff/me/profile', $this->headers)
            ->assertStatus(200);
    });

    it('returns staff projects', function () {
        getJson('/api/v1/staff/'.$this->staffId.'/projects', $this->headers)
            ->assertStatus(200);
    });

    it('returns staff tasks', function () {
        getJson('/api/v1/staff/'.$this->staffId.'/tasks', $this->headers)
            ->assertStatus(200);
    });

    it('returns staff phases', function () {
        getJson('/api/v1/staff/'.$this->staffId.'/phases', $this->headers)
            ->assertStatus(200);
    });

    it('resets staff password', function () {
        postJson('/api/v1/staff/'.$this->staffId.'/reset-password', [], $this->headers)
            ->assertStatus(200);
    });

    it('updates staff status', function () {
        postJson('/api/v1/staff/'.$this->staffId.'/status', [
            'status' => 'active',
        ], $this->headers)
            ->assertStatus(200);
    });

    it('updates staff member', function () {
        putJson('/api/v1/staff/'.$this->staffId, [
            'name' => 'Updated Name',
        ], $this->headers)
            ->assertStatus(200);
    });

    it('rejects status update without status field', function () {
        postJson('/api/v1/staff/'.$this->staffId.'/status', [], $this->headers)
            ->assertStatus(422);
    });

});
