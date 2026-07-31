<?php

use App\Models\LeaveApplication;
use App\Models\PublicHoliday;
use App\Models\StaffLeaveBalance;
use App\Models\StaffProfile;
use App\Models\User;
use Carbon\Carbon;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

function makeHolidayStaffUser(string $role = 'staff'): array
{
    $email = fake()->unique()->safeEmail();
    $user = User::factory()->create(['email' => $email]);
    $user->syncRoles([$role]);

    $staff = StaffProfile::create([
        'name' => fake()->name('ms_MY'),
        'email' => $email,
        'employee_id' => 'EMP-'.rand(1000, 9999),
        'is_active' => true,
        'worker_status' => 'full_time',
        'gender' => 'male',
    ]);

    return [
        'user' => $user,
        'staff' => $staff,
        'headers' => ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken],
    ];
}

beforeEach(function () {
    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

describe('Public holiday CRUD', function () {

    it('blocks plain staff from creating holidays', function () {
        $ctx = makeHolidayStaffUser('staff');

        postJson('/api/v1/public-holidays', [
            'name' => 'Hack Day',
            'date' => '2026-12-31',
        ], $ctx['headers'])
            ->assertStatus(403);
    });

    it('allows all authenticated users to list holidays', function () {
        $ctx = makeHolidayStaffUser('staff');

        getJson('/api/v1/public-holidays', $ctx['headers'])
            ->assertStatus(200);
    });

    it('allows admin to create, update, and delete holidays', function () {
        $response = postJson('/api/v1/public-holidays', [
            'name' => 'Company Anniversary',
            'date' => '2026-07-15',
            'is_recurring' => false,
        ], $this->headers);

        $response->assertStatus(201);
        $id = $response->json('id');

        putJson('/api/v1/public-holidays/'.$id, ['name' => 'Company Anniversary 2'], $this->headers)
            ->assertStatus(200)
            ->assertJsonPath('name', 'Company Anniversary 2');

        deleteJson('/api/v1/public-holidays/'.$id, [], $this->headers)
            ->assertStatus(204);
    });

    it('validates required fields', function () {
        postJson('/api/v1/public-holidays', ['name' => ''], $this->headers)
            ->assertStatus(422);
    });

    it('rejects duplicate holiday for same date', function () {
        PublicHoliday::create(['name' => 'Test Holiday', 'date' => '2026-07-15', 'is_recurring' => false, 'year' => 2026]);

        postJson('/api/v1/public-holidays', [
            'name' => 'Duplicate',
            'date' => '2026-07-15',
            'is_recurring' => false,
        ], $this->headers)
            ->assertStatus(422);
    });

});

describe('Public holiday day counting', function () {

    it('counts working days excluding holidays', function () {
        PublicHoliday::create(['name' => 'Mid-Week Holiday', 'date' => '2026-07-15', 'is_recurring' => false, 'year' => 2026]);

        // Mon 13 - Fri 17 Jul 2026 = 5 weekdays, minus Wed 15 = 4
        $days = LeaveApplication::workingDaysCount(Carbon::parse('2026-07-13'), Carbon::parse('2026-07-17'));

        expect($days)->toBe(4.0);
    });

    it('matches recurring holidays across any year', function () {
        PublicHoliday::create(['name' => 'New Year', 'date' => '2000-01-01', 'is_recurring' => true, 'year' => null]);

        $holiday = PublicHoliday::where('name', 'New Year')->first();
        expect($holiday->isOn(Carbon::parse('2027-01-01')))->toBeTrue()
            ->and($holiday->isOn(Carbon::parse('2027-01-02')))->toBeFalse();
    });

    it('does not match non-recurring holiday in another year', function () {
        PublicHoliday::create(['name' => 'One-Off', 'date' => '2026-07-15', 'is_recurring' => false, 'year' => 2026]);

        $holiday = PublicHoliday::where('name', 'One-Off')->first();
        expect($holiday->isOn(Carbon::parse('2027-07-15')))->toBeFalse();
    });

    it('excludes holidays across a year boundary for recurring dates', function () {
        PublicHoliday::create(['name' => 'New Year', 'date' => '2000-01-01', 'is_recurring' => true, 'year' => null]);

        // Wed 31 Dec 2025 - Fri 2 Jan 2026: weekday count = 31st(Wed), 1st(Thu, holiday), 2nd(Fri) = 2
        $days = LeaveApplication::workingDaysCount(Carbon::parse('2025-12-31'), Carbon::parse('2026-01-02'));

        expect($days)->toBe(2.0);
    });

});

describe('Leave store holiday blocking', function () {

    it('blocks leave when all selected dates are holidays', function () {
        PublicHoliday::create(['name' => 'Wed Holiday', 'date' => '2026-07-15', 'is_recurring' => false, 'year' => 2026]);
        $ctx = makeHolidayStaffUser('staff');

        // Wed 15 Jul only
        postJson('/api/v1/leaves', [
            'staff_id' => $ctx['staff']->id,
            'type' => 'annual',
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-15',
            'reason' => 'test',
        ], $ctx['headers'])
            ->assertStatus(422)
            ->assertJsonPath('errors.0', 'Selected dates are all public holidays or weekends');
    });

    it('allows leave with a holiday in the middle and counts fewer days', function () {
        PublicHoliday::create(['name' => 'Wed Holiday', 'date' => '2026-07-15', 'is_recurring' => false, 'year' => 2026]);
        $ctx = makeHolidayStaffUser('staff');
        $year = 2026;

        StaffLeaveBalance::create([
            'staff_id' => $ctx['staff']->id,
            'type' => 'annual',
            'year' => $year,
            'entitled' => 30,
            'used' => 0,
            'adjusted' => 0,
            'balance' => 30,
        ]);

        // Mon 13 - Fri 17 = 5 weekdays, minus Wed holiday = 4 days requested
        $response = postJson('/api/v1/leaves', [
            'staff_id' => $ctx['staff']->id,
            'type' => 'annual',
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-17',
            'reason' => 'test',
        ], $ctx['headers']);

        $response->assertStatus(201)
            ->assertJsonPath('days', 4);
    });

    it('allows leave on normal working days', function () {
        $ctx = makeHolidayStaffUser('staff');
        $year = 2026;

        StaffLeaveBalance::create([
            'staff_id' => $ctx['staff']->id,
            'type' => 'annual',
            'year' => $year,
            'entitled' => 30,
            'used' => 0,
            'adjusted' => 0,
            'balance' => 30,
        ]);

        postJson('/api/v1/leaves', [
            'staff_id' => $ctx['staff']->id,
            'type' => 'annual',
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-14',
            'reason' => 'test',
        ], $ctx['headers'])
            ->assertStatus(201);
    });

});
