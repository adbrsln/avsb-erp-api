<?php

use App\Models\Project;
use App\Models\StaffProfile;
use App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

function makeStaffPicUser(string $role = 'admin'): array
{
    $email = fake()->unique()->safeEmail();
    $user = User::factory()->create(['email' => $email]);
    $user->syncRoles([$role]);
    StaffProfile::factory()->create(['email' => $email]);

    return [
        'user' => $user,
        'headers' => ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken],
    ];
}

beforeEach(function () {
    $this->ctx = makeStaffPicUser('admin');
});

describe('Project Staff PICs', function () {

    it('lists staff PICs for a project', function () {
        $project = Project::factory()->create();
        $staff = StaffProfile::factory()->create();
        $project->staffPics()->attach($staff->id);

        getJson('/api/v1/projects/'.$project->id.'/staff-pics', $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonCount(1);
    });

    it('assigns staff PICs to a project', function () {
        $project = Project::factory()->create();
        $staffA = StaffProfile::factory()->create();
        $staffB = StaffProfile::factory()->create();

        postJson('/api/v1/projects/'.$project->id.'/staff-pics', [
            'staff_ids' => [$staffA->id, $staffB->id],
        ], $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonCount(2);
    });

    it('requires staff_ids', function () {
        $project = Project::factory()->create();

        postJson('/api/v1/projects/'.$project->id.'/staff-pics', [], $this->ctx['headers'])
            ->assertStatus(422);
    });

    it('detaches a staff PIC', function () {
        $project = Project::factory()->create();
        $staff = StaffProfile::factory()->create();
        $project->staffPics()->attach($staff->id);

        deleteJson('/api/v1/project-staff-pics/'.$project->id.'/'.$staff->id, [], $this->ctx['headers'])
            ->assertStatus(204);

        expect($project->staffPics()->where('staff_id', $staff->id)->exists())->toBeFalse();
    });

});
