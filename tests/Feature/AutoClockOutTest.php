<?php

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\NotificationTemplate;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\artisan;
use function Pest\Laravel\post;
use function Pest\Laravel\putJson;

function autoCloseStaff(string $workerStatus = 'full_time'): array
{
    $email = fake()->unique()->safeEmail();
    $user = User::factory()->create(['email' => $email]);
    $user->syncRoles(['staff']);

    $staff = StaffProfile::create([
        'name' => fake()->name('ms_MY'),
        'email' => $email,
        'employee_id' => 'EMP-'.rand(1000, 9999),
        'is_active' => true,
        'worker_status' => $workerStatus,
        'gender' => 'male',
        'work_start_time' => '08:00',
        'work_end_time' => '17:00',
    ]);

    return [
        'user' => $user,
        'staff' => $staff,
        'headers' => ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken],
    ];
}

function enableAutoClose(int $grace = 60): void
{
    CompanySetting::firstOrCreate(['company_name' => 'Test Company'])
        ->update(['auto_clock_out_enabled' => true, 'auto_clock_out_grace_minutes' => $grace]);
}

function openSession(StaffProfile $staff, string $clockInUtc): Attendance
{
    return Attendance::create([
        'staff_id' => $staff->id,
        'date' => Carbon::parse($clockInUtc)->toDateString(),
        'clock_in' => Carbon::parse($clockInUtc),
        'status' => 'present',
    ]);
}

beforeEach(function () {
    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
    NotificationTemplate::firstOrCreate(
        ['event_type' => 'attendance.auto-closed'],
        [
            'event_type' => 'attendance.auto-closed',
            'category' => 'status',
            'subject_template' => 'Attendance auto-closed — {{date}}',
            'body_template' => '<p>Your attendance for <strong>{{date}}</strong> was auto-closed at <strong>{{clock_out}}</strong>.</p>',
        ]
    );
});

afterEach(function () {
    Carbon::setTestNow();
});

it('does nothing when auto clock-out disabled', function () {
    $ctx = autoCloseStaff();
    $session = openSession($ctx['staff'], '2026-08-14 00:00:00');
    Carbon::setTestNow('2026-08-17 10:30:00');

    artisan('attendance:auto-clock-out')->assertSuccessful();

    expect($session->refresh()->clock_out)->toBeNull();
});

it('closes open session past work end + grace', function () {
    $ctx = autoCloseStaff();
    // clocked 08:00 MY = 00:00 UTC; end 17:00 MY = 09:00 UTC; +60m grace = 10:00 UTC close
    $session = openSession($ctx['staff'], '2026-08-17 00:00:00');
    Carbon::setTestNow('2026-08-17 10:30:00');
    enableAutoClose();

    artisan('attendance:auto-clock-out')->assertSuccessful();

    $session->refresh();
    expect($session->clock_out)->not->toBeNull();
    expect($session->auto_closed)->toBeTrue();
    expect($session->auto_close_reason)->toBe('schedule_end');
    expect($session->clock_out->toDateTimeString())->toBe('2026-08-17 10:00:00');
    expect($session->total_hours)->toBe(10.0);
});

it('leaves sessions inside work window untouched', function () {
    $ctx = autoCloseStaff();
    $session = openSession($ctx['staff'], '2026-08-17 00:30:00');
    Carbon::setTestNow('2026-08-17 07:00:00');
    enableAutoClose();

    artisan('attendance:auto-clock-out')->assertSuccessful();

    expect($session->refresh()->clock_out)->toBeNull();
});

it('skips part-time staff', function () {
    $ctx = autoCloseStaff('part_time');
    $session = openSession($ctx['staff'], '2026-08-17 00:00:00');
    Carbon::setTestNow('2026-08-17 10:30:00');
    enableAutoClose();

    artisan('attendance:auto-clock-out')->assertSuccessful();

    expect($session->refresh()->clock_out)->toBeNull();
});

it('skips staff without work times', function () {
    $ctx = autoCloseStaff();
    $ctx['staff']->update(['work_start_time' => null, 'work_end_time' => null]);
    CompanySetting::firstOrCreate(['company_name' => 'Test Company'])
        ->update(['work_start_time' => null, 'work_end_time' => null]);
    $session = openSession($ctx['staff'], '2026-08-17 00:00:00');
    Carbon::setTestNow('2026-08-17 10:30:00');
    enableAutoClose();

    artisan('attendance:auto-clock-out')->assertSuccessful();

    expect($session->refresh()->clock_out)->toBeNull();
});

it('closes overnight sessions next morning', function () {
    $ctx = autoCloseStaff();
    $ctx['staff']->update(['work_start_time' => '22:00', 'work_end_time' => '06:00']);
    // clocked 22:00 MY day D = 14:00 UTC D; end 06:00 MY day D+1 = 22:00 UTC D; +60m = 23:00 UTC D
    $session = openSession($ctx['staff'], '2026-08-16 14:00:00');
    Carbon::setTestNow('2026-08-16 23:30:00');
    enableAutoClose();

    artisan('attendance:auto-clock-out')->assertSuccessful();

    $session->refresh();
    expect($session->clock_out->toDateTimeString())->toBe('2026-08-16 23:00:00');
    expect($session->total_hours)->toBe(9.0);
});

it('dry-run writes nothing', function () {
    $ctx = autoCloseStaff();
    $session = openSession($ctx['staff'], '2026-08-17 00:00:00');
    Carbon::setTestNow('2026-08-17 10:30:00');
    enableAutoClose();

    artisan('attendance:auto-clock-out --dry-run')->assertSuccessful();

    expect($session->refresh()->clock_out)->toBeNull();
});

it('handles MySQL time column format (H:i:s)', function () {
    $ctx = autoCloseStaff();
    // MySQL `time` columns return "17:00:00", UI sends "17:00"
    $ctx['staff']->update(['work_end_time' => '17:00:00']);
    $session = openSession($ctx['staff'], '2026-08-17 00:00:00');
    Carbon::setTestNow('2026-08-17 10:30:00');
    enableAutoClose();

    artisan('attendance:auto-clock-out')->assertSuccessful();

    $session->refresh();
    expect($session->clock_out->toDateTimeString())->toBe('2026-08-17 10:00:00');
    expect($session->total_hours)->toBe(10.0);
});

it('is idempotent on re-run', function () {
    $ctx = autoCloseStaff();
    $session = openSession($ctx['staff'], '2026-08-17 00:00:00');
    Carbon::setTestNow('2026-08-17 10:30:00');
    enableAutoClose();

    artisan('attendance:auto-clock-out')->assertSuccessful();
    $first = $session->refresh()->clock_out;

    artisan('attendance:auto-clock-out')->assertSuccessful();

    expect($session->refresh()->clock_out->toDateTimeString())->toBe($first->toDateTimeString());
});

it('queues attendance.auto-closed notification', function () {
    $ctx = autoCloseStaff();
    $session = openSession($ctx['staff'], '2026-08-17 00:00:00');
    Carbon::setTestNow('2026-08-17 10:30:00');
    enableAutoClose();

    artisan('attendance:auto-clock-out')->assertSuccessful();

    expect(UserNotification::where('event_type', 'attendance.auto-closed')->count())->toBe(1);
});

it('returns clear 422 when clocking out after auto close', function () {
    $ctx = autoCloseStaff();
    $session = openSession($ctx['staff'], '2026-08-17 00:00:00');
    Carbon::setTestNow('2026-08-17 10:30:00');
    enableAutoClose();
    artisan('attendance:auto-clock-out')->assertSuccessful();

    $res = post('/api/v1/attendance/clock-out/'.$session->id, [
        'latitude' => 3.139,
        'longitude' => 101.6869,
        'accuracy' => 5,
        'photo' => UploadedFile::fake()->image('out.png', 100, 100),
    ], $ctx['headers']);

    $res->assertStatus(422);
    expect($res->json('error'))->toContain('auto-clocked out');
});

it('marks stale-session close on next clock-in', function () {
    $ctx = autoCloseStaff();
    $stale = Attendance::create([
        'staff_id' => $ctx['staff']->id,
        'date' => '2026-08-14',
        'clock_in' => '2026-08-14 00:00:00',
        'status' => 'present',
    ]);
    Carbon::setTestNow('2026-08-17 00:00:00');
    CompanySetting::firstOrCreate(['company_name' => 'Test Company'])
        ->update(['geofence_enforced' => false]);

    post('/api/v1/attendance/clock-in', [
        'latitude' => 3.139,
        'longitude' => 101.6869,
        'accuracy' => 5,
        'photo' => UploadedFile::fake()->image('in.png', 100, 100),
    ], $ctx['headers'])->assertStatus(201);

    $stale->refresh();
    expect($stale->auto_closed)->toBeTrue();
    expect($stale->auto_close_reason)->toBe('stale_session');
});

it('persists auto clock-out settings via company PUT', function () {
    putJson('/api/v1/settings/company', [
        'auto_clock_out_enabled' => true,
        'auto_clock_out_grace_minutes' => 30,
    ], $this->headers)->assertOk();

    $settings = CompanySetting::first();
    expect($settings->auto_clock_out_enabled)->toBeTrue();
    expect((int) $settings->auto_clock_out_grace_minutes)->toBe(30);
});
