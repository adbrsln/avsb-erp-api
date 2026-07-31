<?php

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Project;
use App\Models\StaffProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

function makeStaffUser(string $role = 'staff', string $workerStatus = 'full_time'): array
{
    $email = fake()->unique()->safeEmail();
    $user = User::factory()->create(['email' => $email]);
    $user->syncRoles([$role]);

    $staff = StaffProfile::create([
        'name' => fake()->name('ms_MY'),
        'email' => $email,
        'employee_id' => 'EMP-'.rand(1000, 9999),
        'is_active' => true,
        'worker_status' => $workerStatus,
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

afterEach(function () {
    Carbon::setTestNow();
});

function setCompanyHours(?string $start, ?string $end): CompanySetting
{
    return CompanySetting::firstOrCreate(
        ['company_name' => 'Test Company'],
        ['work_start_time' => $start, 'work_end_time' => $end],
    );
}

function punchIn(array $ctx, ?string $projectId = null): mixed
{
    $body = [
        'latitude' => 3.139,
        'longitude' => 101.6869,
        'accuracy' => 5,
        'photo' => UploadedFile::fake()->image('punch.png', 100, 100),
    ];
    if ($projectId) {
        $body['project_id'] = $projectId;
    }

    return post('/api/v1/attendance/clock-in', $body, $ctx['headers']);
}

describe('Attendance role gating', function () {

    it('blocks plain staff from records', function () {
        $ctx = makeStaffUser('staff');

        getJson('/api/v1/attendance/records', $ctx['headers'])
            ->assertStatus(403);
    });

    it('blocks plain staff from summary', function () {
        $ctx = makeStaffUser('staff');

        getJson('/api/v1/attendance/summary', $ctx['headers'])
            ->assertStatus(403);
    });

    it('blocks plain staff from export', function () {
        $ctx = makeStaffUser('staff');

        getJson('/api/v1/attendance/export', $ctx['headers'])
            ->assertStatus(403);
    });

    it('blocks plain staff from clearing flags', function () {
        $ctx = makeStaffUser('staff');
        $record = Attendance::factory()->flagged()->create();

        postJson('/api/v1/attendance/'.$record->id.'/clear-flag', [], $ctx['headers'])
            ->assertStatus(403);
    });

    it('allows pm on records and summary', function () {
        $ctx = makeStaffUser('pm');

        getJson('/api/v1/attendance/records', $ctx['headers'])->assertStatus(200);
        getJson('/api/v1/attendance/summary', $ctx['headers'])->assertStatus(200);
        getJson('/api/v1/attendance/export', $ctx['headers'])->assertStatus(200);
    });

    it('returns all monthly records with all=true regardless of page size', function () {
        $ctx = makeStaffUser('pm');

        Attendance::factory()->count(5)->create();

        getJson('/api/v1/attendance/records?all=true', $ctx['headers'])
            ->assertStatus(200)
            ->assertJsonCount(5, 'data');
    });

    it('allows hr to clear flags', function () {
        $ctx = makeStaffUser('hr');
        $record = Attendance::factory()->flagged()->create();

        postJson('/api/v1/attendance/'.$record->id.'/clear-flag', [], $ctx['headers'])
            ->assertStatus(200);
    });

    it('allows finance on summary but not records', function () {
        $ctx = makeStaffUser('finance');

        getJson('/api/v1/attendance/summary', $ctx['headers'])->assertStatus(200);
        getJson('/api/v1/attendance/records', $ctx['headers'])->assertStatus(403);
    });

});

describe('Attendance geo validation', function () {

    it('rejects out-of-range latitude on clock-in', function () {
        $ctx = makeStaffUser('staff');

        postJson('/api/v1/attendance/clock-in', [
            'latitude' => 95,
            'longitude' => 101.7,
        ], $ctx['headers'])
            ->assertStatus(422)
            ->assertJson(['error' => 'Latitude must be between -90 and 90.']);
    });

    it('rejects missing longitude when latitude provided', function () {
        $ctx = makeStaffUser('staff');

        postJson('/api/v1/attendance/clock-in', [
            'latitude' => 3.139,
        ], $ctx['headers'])
            ->assertStatus(422)
            ->assertJson(['error' => 'Both latitude and longitude are required together.']);
    });

    it('rejects non-numeric coordinates', function () {
        $ctx = makeStaffUser('staff');

        postJson('/api/v1/attendance/clock-in', [
            'latitude' => 'abc',
            'longitude' => '101.7',
        ], $ctx['headers'])
            ->assertStatus(422)
            ->assertJson(['error' => 'Latitude and longitude must be numeric.']);
    });

    it('rejects out-of-range longitude on clock-out', function () {
        $ctx = makeStaffUser('staff');
        $record = Attendance::factory()->create([
            'staff_id' => $ctx['staff']->id,
            'clock_out' => null,
        ]);

        postJson('/api/v1/attendance/clock-out/'.$record->id, [
            'latitude' => 3.139,
            'longitude' => 200,
        ], $ctx['headers'])
            ->assertStatus(422)
            ->assertJson(['error' => 'Longitude must be between -180 and 180.']);
    });

});

describe('Attendance summary', function () {

    it('counts distinct days, not rows, for multiple punches per day', function () {
        $ctx = makeStaffUser('pm');
        $staff = $ctx['staff'];

        $date = now()->toDateString();
        Attendance::factory()->create(['staff_id' => $staff->id, 'date' => $date]);
        Attendance::factory()->create(['staff_id' => $staff->id, 'date' => $date]);
        Attendance::factory()->create(['staff_id' => $staff->id, 'date' => now()->subDay()->toDateString()]);

        getJson('/api/v1/attendance/summary?date_from='.now()->startOfMonth()->toDateString().'&date_to='.now()->toDateString(), $ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('total_days', 2)
            ->assertJsonCount(1, 'by_staff')
            ->assertJsonPath('by_staff.0.total_days', 2);
    });

});

describe('Attendance CSV export', function () {

    it('exports clock-in and clock-out coordinates', function () {
        $ctx = makeStaffUser('pm');
        Attendance::factory()->create([
            'staff_id' => $ctx['staff']->id,
            'clock_in_latitude' => 3.139,
            'clock_in_longitude' => 101.6869,
            'clock_out_latitude' => 3.14,
            'clock_out_longitude' => 101.69,
        ]);

        $response = get('/api/v1/attendance/export', $ctx['headers']);
        $response->assertStatus(200);

        $csv = $response->getContent();

        expect($csv)->toContain('Clock-In Lat')
            ->toContain('Clock-Out Lat')
            ->toContain('3.139')
            ->toContain('101.6869')
            ->toContain('3.14')
            ->toContain('101.69');
    });

});

describe('Attendance schedule window detection', function () {

    it('flags clock-in before company default window start', function () {
        setCompanyHours('09:00', '18:00');
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));

        $ctx = makeStaffUser('staff');
        punchIn($ctx)
            ->assertStatus(201)
            ->assertJsonPath('schedule_flagged', true)
            ->assertJsonPath('schedule_flag_reason', 'Clock-in outside scheduled window (09:00–18:00).');
    });

    it('does not flag clock-in within window with grace', function () {
        setCompanyHours('09:00', '18:00');
        Carbon::setTestNow(Carbon::today()->setTime(9, 10));

        $ctx = makeStaffUser('staff');
        punchIn($ctx)
            ->assertStatus(201)
            ->assertJsonPath('schedule_flagged', false)
            ->assertJsonPath('schedule_flag_reason', null);
    });

    it('flags clock-in after company default window end', function () {
        setCompanyHours('09:00', '18:00');
        Carbon::setTestNow(Carbon::today()->setTime(19, 0));

        $ctx = makeStaffUser('staff');
        punchIn($ctx)
            ->assertStatus(201)
            ->assertJsonPath('schedule_flagged', true)
            ->assertJsonPath('schedule_flag_reason', 'Clock-in outside scheduled window (09:00–18:00).');
    });

    it('skips part-time workers', function () {
        setCompanyHours('09:00', '18:00');
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));

        $ctx = makeStaffUser('staff', 'part_time');
        $project = Project::first();

        punchIn($ctx, (string) $project->id)
            ->assertStatus(201)
            ->assertJsonPath('schedule_flagged', false);
    });

    it('does not flag when no schedule is configured', function () {
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));

        $ctx = makeStaffUser('staff');
        punchIn($ctx)
            ->assertStatus(201)
            ->assertJsonPath('schedule_flagged', false);
    });

    it('uses staff override over company default', function () {
        setCompanyHours('09:00', '18:00');
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));

        $ctx = makeStaffUser('staff');
        $ctx['staff']->update(['work_start_time' => '07:00', 'work_end_time' => '16:00']);

        punchIn($ctx)
            ->assertStatus(201)
            ->assertJsonPath('schedule_flagged', false);
    });

    it('handles overnight shifts', function () {
        setCompanyHours('09:00', '18:00');
        Carbon::setTestNow(Carbon::today()->setTime(23, 0));

        $ctx = makeStaffUser('staff');
        $ctx['staff']->update(['work_start_time' => '22:00', 'work_end_time' => '06:00']);

        punchIn($ctx)
            ->assertStatus(201)
            ->assertJsonPath('schedule_flagged', false);
    });

    it('flags overnight-shift punch in the middle of the day', function () {
        setCompanyHours('09:00', '18:00');
        Carbon::setTestNow(Carbon::today()->setTime(12, 0));

        $ctx = makeStaffUser('staff');
        $ctx['staff']->update(['work_start_time' => '22:00', 'work_end_time' => '06:00']);

        punchIn($ctx)
            ->assertStatus(201)
            ->assertJsonPath('schedule_flagged', true)
            ->assertJsonPath('schedule_flag_reason', 'Clock-in outside scheduled window (22:00–06:00).');
    });

    it('flags clock-out after window end', function () {
        setCompanyHours('09:00', '18:00');
        $ctx = makeStaffUser('staff');

        $record = Attendance::factory()->create([
            'staff_id' => $ctx['staff']->id,
            'clock_out' => null,
        ]);

        Carbon::setTestNow(Carbon::today()->setTime(19, 0));

        post('/api/v1/attendance/clock-out/'.$record->id, [
            'latitude' => 3.139,
            'longitude' => 101.6869,
            'accuracy' => 5,
            'photo' => UploadedFile::fake()->image('punch.png', 100, 100),
        ], $ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('schedule_flagged', true)
            ->assertJsonPath('schedule_flag_reason', 'Clock-out outside scheduled window (09:00–18:00).');
    });

    it('clears schedule flag via clear-flag endpoint', function () {
        $ctx = makeStaffUser('hr');
        $record = Attendance::factory()->create([
            'schedule_flagged' => true,
            'schedule_flag_reason' => 'Clock-in outside scheduled window (09:00–18:00).',
        ]);

        postJson('/api/v1/attendance/'.$record->id.'/clear-flag', [], $ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('schedule_flagged', false)
            ->assertJsonPath('schedule_flag_reason', null);
    });

});
