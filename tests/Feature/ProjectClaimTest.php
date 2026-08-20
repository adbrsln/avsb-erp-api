<?php

use App\Models\Project;
use App\Models\ProjectClaim;
use App\Models\StaffProfile;
use App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

function makeClaimUser(string $role = 'admin'): array
{
    $email = fake()->unique()->safeEmail();
    $user = User::factory()->create(['email' => $email]);
    $user->syncRoles([$role]);

    $staff = StaffProfile::factory()->create(['email' => $email]);

    return [
        'user' => $user,
        'staff' => $staff,
        'headers' => ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken],
    ];
}

function makeClaimProject(): Project
{
    return Project::factory()->create(['status' => 'active']);
}

beforeEach(function () {
    $this->ctx = makeClaimUser('admin');
});

describe('Project Claims CRUD', function () {

    it('lists claims for a project member', function () {
        $project = makeClaimProject();
        ProjectClaim::create([
            'claim_number' => 'CLM-'.rand(1000, 9999),
            'project_id' => $project->id,
            'title' => 'Site work',
            'amount' => 100,
            'status' => 'draft',
        ]);

        getJson('/api/v1/projects/'.$project->id.'/claims', $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    it('forbids a non-member staff from listing claims', function () {
        $project = makeClaimProject();
        $staff = makeClaimUser('staff');

        getJson('/api/v1/projects/'.$project->id.'/claims', $staff['headers'])
            ->assertStatus(403);
    });

    it('creates a draft claim with summed amount and generated number', function () {
        $project = makeClaimProject();

        postJson('/api/v1/projects/'.$project->id.'/claims', [
            'title' => 'Paving works',
            'description' => 'Milling and repaving',
            'items' => [
                ['description' => 'Milling', 'amount' => 500],
                ['description' => 'Paving', 'amount' => 250.5],
            ],
        ], $this->ctx['headers'])
            ->assertStatus(201)
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('amount', 750.5)
            ->assertJsonStructure(['claim_number', 'id']);
    });

    it('validates title is required', function () {
        $project = makeClaimProject();

        postJson('/api/v1/projects/'.$project->id.'/claims', ['description' => 'No title'], $this->ctx['headers'])
            ->assertStatus(422);
    });

    it('shows a claim to a member', function () {
        $project = makeClaimProject();
        $claim = ProjectClaim::create([
            'claim_number' => 'CLM-'.rand(1000, 9999),
            'project_id' => $project->id,
            'title' => 'Show me',
            'amount' => 10,
            'status' => 'draft',
        ]);

        getJson('/api/v1/project-claims/'.$claim->id, $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('id', $claim->id);
    });

    it('updates a draft claim and recomputes amount', function () {
        $project = makeClaimProject();
        $claim = ProjectClaim::create([
            'claim_number' => 'CLM-'.rand(1000, 9999),
            'project_id' => $project->id,
            'title' => 'Old title',
            'amount' => 0,
            'status' => 'draft',
        ]);

        putJson('/api/v1/project-claims/'.$claim->id, [
            'title' => 'New title',
            'items' => [['description' => 'x', 'amount' => 99]],
        ], $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('title', 'New title')
            ->assertJsonPath('amount', 99);
    });

    it('blocks editing a non-draft claim', function () {
        $project = makeClaimProject();
        $claim = ProjectClaim::create([
            'claim_number' => 'CLM-'.rand(1000, 9999),
            'project_id' => $project->id,
            'title' => 'Submitted',
            'amount' => 1,
            'status' => 'submitted',
        ]);

        putJson('/api/v1/project-claims/'.$claim->id, ['title' => 'Nope'], $this->ctx['headers'])
            ->assertStatus(422);
    });

    it('deletes a draft claim as pm+', function () {
        $project = makeClaimProject();
        $claim = ProjectClaim::create([
            'claim_number' => 'CLM-'.rand(1000, 9999),
            'project_id' => $project->id,
            'title' => 'Delete me',
            'amount' => 0,
            'status' => 'draft',
        ]);

        deleteJson('/api/v1/project-claims/'.$claim->id, [], $this->ctx['headers'])
            ->assertStatus(204);

        expect(ProjectClaim::find($claim->id))->toBeNull();
    });

});

describe('Project Claims workflow', function () {

    it('runs submit -> approve -> mark paid', function () {
        $project = makeClaimProject();
        $claim = ProjectClaim::create([
            'claim_number' => 'CLM-'.rand(1000, 9999),
            'project_id' => $project->id,
            'title' => 'Workflow',
            'amount' => 50,
            'status' => 'draft',
        ]);

        postJson('/api/v1/project-claims/'.$claim->id.'/submit', [], $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'submitted');

        postJson('/api/v1/project-claims/'.$claim->id.'/approve', [], $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'approved');

        postJson('/api/v1/project-claims/'.$claim->id.'/mark-paid', [], $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'paid');
    });

    it('rejects a wrong-order transition', function () {
        $project = makeClaimProject();
        $claim = ProjectClaim::create([
            'claim_number' => 'CLM-'.rand(1000, 9999),
            'project_id' => $project->id,
            'title' => 'Draft only',
            'amount' => 1,
            'status' => 'draft',
        ]);

        postJson('/api/v1/project-claims/'.$claim->id.'/approve', [], $this->ctx['headers'])
            ->assertStatus(422);
    });

    it('rejects a submitted claim', function () {
        $project = makeClaimProject();
        $claim = ProjectClaim::create([
            'claim_number' => 'CLM-'.rand(1000, 9999),
            'project_id' => $project->id,
            'title' => 'Reject me',
            'amount' => 1,
            'status' => 'submitted',
        ]);

        postJson('/api/v1/project-claims/'.$claim->id.'/reject', [], $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'rejected');
    });

    it('blocks marking an approved claim paid by non-admin roles', function () {
        $project = makeClaimProject();
        $ctx = makeClaimUser('pm');
        $claim = ProjectClaim::create([
            'claim_number' => 'CLM-'.rand(1000, 9999),
            'project_id' => $project->id,
            'title' => 'Approved',
            'amount' => 1,
            'status' => 'approved',
        ]);

        postJson('/api/v1/project-claims/'.$claim->id.'/mark-paid', [], $ctx['headers'])
            ->assertStatus(403);
    });

});
