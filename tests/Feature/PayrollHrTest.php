<?php

use App\Models\ExpenseClaim;
use App\Models\LeaveApplication;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

describe('Payroll', function () {

    it('returns 401 without token', function () {
        getJson('/api/v1/payroll/periods')->assertStatus(401);
    });

    it('lists payroll periods', function () {
        getJson('/api/v1/payroll/periods', $this->headers)
            ->assertStatus(200);
    });

    it('returns my payslips', function () {
        getJson('/api/v1/my-payslips', $this->headers)
            ->assertStatus(200);
    });

});

describe('Leaves', function () {

    it('lists leaves', function () {
        getJson('/api/v1/leaves', $this->headers)
            ->assertStatus(200);
    });

    it('shows single leave', function () {
        $leave = LeaveApplication::first();
        if (! $leave) {
            $this->markTestSkipped('No leaves in database');
        }

        getJson('/api/v1/leaves/'.$leave->id, $this->headers)
            ->assertStatus(200);
    });

    it('creates leave with validation error', function () {
        postJson('/api/v1/leaves', [], $this->headers)
            ->assertStatus(422);
    });

});

describe('Claims', function () {

    it('lists all claims', function () {
        getJson('/api/v1/claims', $this->headers)
            ->assertStatus(200);
    });

    it('lists my claims', function () {
        getJson('/api/v1/my-claims', $this->headers)
            ->assertStatus(200);
    });

    it('shows single claim', function () {
        $claim = ExpenseClaim::first();
        if (! $claim) {
            $this->markTestSkipped('No claims in database');
        }

        getJson('/api/v1/claims/'.$claim->id, $this->headers)
            ->assertStatus(200);
    });

});

describe('Timecards', function () {

    it('lists timecards', function () {
        getJson('/api/v1/timecards', $this->headers)
            ->assertStatus(200);
    });

});

describe('Attendance', function () {

    it('returns attendance records', function () {
        getJson('/api/v1/attendance/records', $this->headers)
            ->assertStatus(200);
    });

    it('returns today attendance', function () {
        getJson('/api/v1/attendance/today', $this->headers)
            ->assertStatus(200);
    });

    it('returns attendance summary', function () {
        getJson('/api/v1/attendance/summary', $this->headers)
            ->assertStatus(200);
    });

});

describe('Leave Groups', function () {

    it('lists leave groups', function () {
        getJson('/api/v1/leave-groups', $this->headers)
            ->assertStatus(200);
    });

});
