<?php

use App\Models\Attendance;
use App\Models\Geofence;
use App\Models\Project;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

function makeGeofenceStaffUser(string $role = 'staff'): array
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

describe('Geofence CRUD', function () {

    it('blocks plain staff from creating geofences', function () {
        $ctx = makeGeofenceStaffUser('staff');

        postJson('/api/v1/geofences', [
            'name' => 'Site A',
            'latitude' => 3.139,
            'longitude' => 101.6869,
            'radius_meters' => 100,
        ], $ctx['headers'])
            ->assertStatus(403);
    });

    it('blocks plain staff from updating geofences', function () {
        $ctx = makeGeofenceStaffUser('staff');
        $geofence = Geofence::first();

        putJson('/api/v1/geofences/'.$geofence->id, ['name' => 'Hacked'], $ctx['headers'])
            ->assertStatus(403);
    });

    it('blocks plain staff from deleting geofences', function () {
        $ctx = makeGeofenceStaffUser('staff');
        $geofence = Geofence::first();

        deleteJson('/api/v1/geofences/'.$geofence->id, [], $ctx['headers'])
            ->assertStatus(403);
    });

    it('allows all authenticated users to list geofences', function () {
        $ctx = makeGeofenceStaffUser('staff');

        getJson('/api/v1/geofences?active=1&all=true', $ctx['headers'])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    it('allows admin to create, update, and delete geofences', function () {
        $response = postJson('/api/v1/geofences', [
            'name' => 'Site B',
            'description' => 'Second site',
            'latitude' => 3.2,
            'longitude' => 101.7,
            'radius_meters' => 250,
            'is_active' => true,
        ], $this->headers);

        $response->assertStatus(201);
        $id = $response->json('id');
        expect($id)->not->toBeNull();

        putJson('/api/v1/geofences/'.$id, ['radius_meters' => 500], $this->headers)
            ->assertStatus(200)
            ->assertJsonPath('radius_meters', 500);

        deleteJson('/api/v1/geofences/'.$id, [], $this->headers)
            ->assertStatus(204);
    });

    it('validates required fields on create', function () {
        postJson('/api/v1/geofences', ['name' => ''], $this->headers)
            ->assertStatus(422)
            ->assertJson(['error' => 'Name is required']);

        postJson('/api/v1/geofences', ['name' => 'Site C'], $this->headers)
            ->assertStatus(422)
            ->assertJson(['error' => 'Latitude and longitude are required']);
    });

});

describe('Geofence punch enforcement', function () {

    it('blocks clock-in when no active geofence exists', function () {
        $ctx = makeGeofenceStaffUser('staff');
        Geofence::query()->update(['is_active' => false]);

        postJson('/api/v1/attendance/clock-in', [
            'latitude' => 3.139,
            'longitude' => 101.6869,
            'accuracy' => 5,
        ], $ctx['headers'])
            ->assertStatus(422)
            ->assertJson(['error' => 'No active geofenced sites are configured. Contact an administrator.']);
    });

    it('blocks clock-in when location is outside all geofences', function () {
        $ctx = makeGeofenceStaffUser('staff');

        postJson('/api/v1/attendance/clock-in', [
            'latitude' => 5.0,
            'longitude' => 110.0,
            'accuracy' => 5,
        ], $ctx['headers'])
            ->assertStatus(422)
            ->assertJson(['error' => 'Location is outside all geofenced sites.']);
    });

    it('blocks clock-in when GPS accuracy exceeds 50m', function () {
        $ctx = makeGeofenceStaffUser('staff');

        postJson('/api/v1/attendance/clock-in', [
            'latitude' => 3.139,
            'longitude' => 101.6869,
            'accuracy' => 60,
        ], $ctx['headers'])
            ->assertStatus(422)
            ->assertJson(['error' => 'GPS accuracy must be within 50m to punch.']);
    });

    it('blocks clock-in when accuracy is missing', function () {
        $ctx = makeGeofenceStaffUser('staff');

        postJson('/api/v1/attendance/clock-in', [
            'latitude' => 3.139,
            'longitude' => 101.6869,
        ], $ctx['headers'])
            ->assertStatus(422)
            ->assertJson(['error' => 'A valid GPS accuracy reading is required.']);
    });

    it('records geofence_id on successful clock-in', function () {
        $ctx = makeGeofenceStaffUser('staff');
        $geofence = Geofence::where('is_active', true)->first();

        $response = post('/api/v1/attendance/clock-in', [
            'latitude' => 3.139,
            'longitude' => 101.6869,
            'accuracy' => 5,
            'photo' => UploadedFile::fake()->image('punch.png', 100, 100),
        ], $ctx['headers']);

        $response->assertStatus(201);
        $response->assertJsonPath('geofence_id', $geofence->id);
        expect($response->json('geofence_id'))->toBe($geofence->id);
    });

    it('records clock_out_geofence_id on successful clock-out', function () {
        $ctx = makeGeofenceStaffUser('staff');
        $geofence = Geofence::where('is_active', true)->first();

        $record = Attendance::factory()->create([
            'staff_id' => $ctx['staff']->id,
            'clock_out' => null,
        ]);

        $response = post('/api/v1/attendance/clock-out/'.$record->id, [
            'latitude' => 3.139,
            'longitude' => 101.6869,
            'accuracy' => 5,
            'photo' => UploadedFile::fake()->image('punch.png', 100, 100),
        ], $ctx['headers']);

        $response->assertStatus(200);
        $response->assertJsonPath('clock_out_geofence_id', $geofence->id);
    });

    it('enforces geofence for proxy clock-in by admin', function () {
        $staff = StaffProfile::where('email', 'superadmin@azamventures.com')->first();

        postJson('/api/v1/attendance/clock-in', [
            'staff_id' => $staff->id,
            'latitude' => 5.0,
            'longitude' => 110.0,
            'accuracy' => 5,
        ], $this->headers)
            ->assertStatus(422)
            ->assertJson(['error' => 'Location is outside all geofenced sites.']);
    });

});

describe('Project auto-geofence sync', function () {

    it('creates an active geofence for an active project with coordinates', function () {
        $project = Project::factory()->create([
            'status' => 'active',
            'latitude' => 3.2,
            'longitude' => 101.7,
            'radius_meters' => 150,
        ]);

        $geofence = Geofence::where('project_id', $project->id)->first();
        expect($geofence)->not->toBeNull()
            ->and($geofence->is_active)->toBeTrue()
            ->and($geofence->radius_meters)->toBe(150)
            ->and((float) $geofence->latitude)->toBe(3.2);
    });

    it('deactivates the geofence when the project leaves active status', function () {
        $project = Project::factory()->create([
            'status' => 'active',
            'latitude' => 3.2,
            'longitude' => 101.7,
            'radius_meters' => 100,
        ]);

        $project->update(['status' => 'paused']);

        $geofence = Geofence::where('project_id', $project->id)->first();
        expect($geofence->is_active)->toBeFalse();
    });

    it('does not create a geofence when project has no coordinates', function () {
        $project = Project::factory()->create([
            'status' => 'active',
            'latitude' => null,
            'longitude' => null,
        ]);

        expect(Geofence::where('project_id', $project->id)->count())->toBe(0);
    });

    it('preserves admin radius edits on subsequent project saves', function () {
        $project = Project::factory()->create([
            'status' => 'active',
            'latitude' => 3.2,
            'longitude' => 101.7,
            'radius_meters' => 100,
        ]);
        $geofence = Geofence::where('project_id', $project->id)->first();

        $geofence->update(['radius_meters' => 250]);
        $project->update(['name' => 'Renamed Project']);

        $geofence->refresh();
        expect($geofence->radius_meters)->toBe(250);
    });

    it('updates geofence coordinates when project coordinates change', function () {
        $project = Project::factory()->create([
            'status' => 'active',
            'latitude' => 3.2,
            'longitude' => 101.7,
            'radius_meters' => 100,
        ]);

        $project->update(['latitude' => 3.5, 'longitude' => 101.9]);

        $geofence = Geofence::where('project_id', $project->id)->first();
        expect((float) $geofence->latitude)->toBe(3.5)
            ->and((float) $geofence->longitude)->toBe(101.9);
    });

    it('deactivates the geofence when the project is deleted', function () {
        $project = Project::factory()->create([
            'status' => 'active',
            'latitude' => 3.2,
            'longitude' => 101.7,
            'radius_meters' => 100,
        ]);

        $project->delete();

        $geofence = Geofence::where('project_id', $project->id)->first();
        expect($geofence)->not->toBeNull()
            ->and($geofence->is_active)->toBeFalse();
    });

    it('deactivates the geofence when coordinates are cleared', function () {
        $project = Project::factory()->create([
            'status' => 'active',
            'latitude' => 3.2,
            'longitude' => 101.7,
            'radius_meters' => 100,
        ]);

        $project->update(['latitude' => null, 'longitude' => null]);

        $geofence = Geofence::where('project_id', $project->id)->first();
        expect($geofence)->not->toBeNull()
            ->and($geofence->is_active)->toBeFalse();
    });

});
