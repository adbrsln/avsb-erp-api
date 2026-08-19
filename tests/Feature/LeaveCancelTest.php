<?php

use App\Models\LeaveApplication;
use App\Models\StaffLeaveBalance;
use App\Models\StaffProfile;
use App\Models\User;
use Carbon\Carbon;

use function Pest\Laravel\postJson;

function makeLeaveActorUser(string $role = 'staff'): array
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

function makeLeaveFor(StaffProfile $staff, string $status, string $startDate, string $endDate): LeaveApplication
{
    return LeaveApplication::create([
        'leave_ref' => 'LV-TEST-'.rand(1000, 9999),
        'staff_id' => $staff->id,
        'type' => 'annual',
        'start_date' => $startDate,
        'end_date' => $endDate,
        'reason' => 'test',
        'status' => $status,
    ]);
}

// Next upcoming Mon-Tue-Wed window that starts today or later (start_date >= today
// passes the "already started" guard). Guarantees exactly 3 working days, any run date.
function nextMonWedWindow(): array
{
    $start = Carbon::today();
    while ($start->dayOfWeek !== Carbon::MONDAY) {
        $start->addDay();
    }

    return [$start->toDateString(), $start->addDays(2)->toDateString()];
}

beforeEach(function () {
    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

describe('Leave cancel', function () {

    it('cancels a pending leave without touching balance', function () {
        $ctx = makeLeaveActorUser('staff');
        $leave = makeLeaveFor($ctx['staff'], 'pending', Carbon::today()->addDays(5)->toDateString(), Carbon::today()->addDays(5)->toDateString());

        postJson('/api/v1/leaves/'.$leave->id.'/cancel', [], $ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('cancelled_at', fn ($v) => $v !== null);
    });

    it('withdraws an approved leave and restores balance', function () {
        $ctx = makeLeaveActorUser('staff');
        $year = 2026;

        StaffLeaveBalance::create([
            'staff_id' => $ctx['staff']->id,
            'type' => 'annual',
            'year' => $year,
            'entitled' => 14,
            'used' => 3,
            'adjusted' => 0,
            'balance' => 11,
        ]);

        // Next upcoming Mon-Wed = 3 working days (weekends/holidays excluded)
        [$startDate, $endDate] = nextMonWedWindow();
        $leave = makeLeaveFor($ctx['staff'], 'approved', $startDate, $endDate);

        postJson('/api/v1/leaves/'.$leave->id.'/cancel', [], $ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'cancelled');

        $balance = StaffLeaveBalance::where('staff_id', $ctx['staff']->id)->where('type', 'annual')->where('year', $year)->first();
        expect((float) $balance->used)->toBe(0.0)
            ->and((float) $balance->balance)->toBe(14.0);
    });

    it('rejects cancelling an already rejected leave', function () {
        $ctx = makeLeaveActorUser('staff');
        $leave = makeLeaveFor($ctx['staff'], 'rejected', Carbon::today()->addDays(5)->toDateString(), Carbon::today()->addDays(5)->toDateString());

        postJson('/api/v1/leaves/'.$leave->id.'/cancel', [], $ctx['headers'])
            ->assertStatus(422);
    });

    it('blocks withdrawing approved leave that has already started', function () {
        $ctx = makeLeaveActorUser('staff');
        $leave = makeLeaveFor($ctx['staff'], 'approved', Carbon::today()->subDays(2)->toDateString(), Carbon::today()->addDays(2)->toDateString());

        postJson('/api/v1/leaves/'.$leave->id.'/cancel', [], $ctx['headers'])
            ->assertStatus(422);
    });

    it('forbids another staff from cancelling someone else\'s leave', function () {
        $owner = makeLeaveActorUser('staff');
        $other = makeLeaveActorUser('staff');
        $leave = makeLeaveFor($owner['staff'], 'pending', Carbon::today()->addDays(5)->toDateString(), Carbon::today()->addDays(5)->toDateString());

        postJson('/api/v1/leaves/'.$leave->id.'/cancel', [], $other['headers'])
            ->assertStatus(403);
    });

    it('allows hr to cancel on behalf of staff', function () {
        $owner = makeLeaveActorUser('staff');
        $hr = makeLeaveActorUser('hr');
        $leave = makeLeaveFor($owner['staff'], 'pending', Carbon::today()->addDays(5)->toDateString(), Carbon::today()->addDays(5)->toDateString());

        postJson('/api/v1/leaves/'.$leave->id.'/cancel', [], $hr['headers'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'cancelled');
    });

});
