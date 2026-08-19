<?php

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\ProjectSubcontractor;
use App\Models\StaffProfile;
use App\Models\Subcontractor;
use App\Models\SubcontractorClaim;
use App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

function makeSubconUser(string $role = 'admin'): array
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

function ensureRetentionAccounts(): void
{
    ChartOfAccount::firstOrCreate(['code' => '1102'], ['name' => 'Bank', 'type' => 'asset', 'is_active' => true]);
    ChartOfAccount::firstOrCreate(['code' => '2109'], ['name' => 'Retention Payable', 'type' => 'liability', 'is_active' => true]);
}

function makeSubconAssignment(Project $project, Subcontractor $sub): ProjectSubcontractor
{
    return ProjectSubcontractor::create([
        'project_id' => $project->id,
        'subcontractor_id' => $sub->id,
        'scope_of_work' => 'Paving',
        'contract_value' => 10000,
        'retention_pct' => 10,
        'retention_amount' => 1000,
        'status' => 'active',
    ]);
}

beforeEach(function () {
    $this->ctx = makeSubconUser('admin');
    ensureRetentionAccounts();
});

describe('Project Subcontractor CRUD', function () {

    it('lists assignments for a project', function () {
        $project = Project::factory()->create();
        $sub = Subcontractor::factory()->create();
        makeSubconAssignment($project, $sub);

        getJson('/api/v1/projects/'.$project->id.'/subcontractors', $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    it('creates an assignment', function () {
        $project = Project::factory()->create();
        $sub = Subcontractor::factory()->create();

        postJson('/api/v1/projects/'.$project->id.'/subcontractors', [
            'subcontractor_id' => $sub->id,
            'scope_of_work' => 'Road marking',
            'contract_value' => 5000,
            'retention_pct' => 5,
        ], $this->ctx['headers'])
            ->assertStatus(201)
            ->assertJsonPath('subcontractor_id', $sub->id)
            ->assertJsonPath('status', 'active');
    });

    it('requires subcontractor_id', function () {
        $project = Project::factory()->create();

        postJson('/api/v1/projects/'.$project->id.'/subcontractors', ['scope_of_work' => 'X'], $this->ctx['headers'])
            ->assertStatus(422);
    });

    it('shows an assignment', function () {
        $project = Project::factory()->create();
        $sub = Subcontractor::factory()->create();
        $assignment = makeSubconAssignment($project, $sub);

        getJson('/api/v1/project-subcontractors/'.$assignment->id, $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('id', $assignment->id);
    });

    it('updates an assignment', function () {
        $project = Project::factory()->create();
        $sub = Subcontractor::factory()->create();
        $assignment = makeSubconAssignment($project, $sub);

        putJson('/api/v1/project-subcontractors/'.$assignment->id, ['scope_of_work' => 'Updated scope'], $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('scope_of_work', 'Updated scope');
    });

    it('deletes an assignment without blocking claims', function () {
        $project = Project::factory()->create();
        $sub = Subcontractor::factory()->create();
        $assignment = makeSubconAssignment($project, $sub);

        deleteJson('/api/v1/project-subcontractors/'.$assignment->id, [], $this->ctx['headers'])
            ->assertStatus(204);

        expect(ProjectSubcontractor::find($assignment->id))->toBeNull();
    });

    it('blocks deletion when approved or paid claims exist', function () {
        $project = Project::factory()->create();
        $sub = Subcontractor::factory()->create();
        $assignment = makeSubconAssignment($project, $sub);
        SubcontractorClaim::create([
            'project_subcontractor_id' => $assignment->id,
            'claim_number' => 'SC-'.rand(1000, 9999),
            'claim_date' => now()->toDateString(),
            'claimed_amount' => 100,
            'net_payable' => 100,
            'status' => 'approved',
        ]);

        deleteJson('/api/v1/project-subcontractors/'.$assignment->id, [], $this->ctx['headers'])
            ->assertStatus(422);
    });

});

describe('Project Subcontractor retention release', function () {

    it('releases CC retention and creates a journal entry', function () {
        $project = Project::factory()->create();
        $sub = Subcontractor::factory()->create();
        $assignment = makeSubconAssignment($project, $sub);

        postJson('/api/v1/project-subcontractors/'.$assignment->id.'/release-retention', [
            'amount' => 500,
            'stage' => 'cc',
        ], $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('retention_released_at_cc', 500);

        $je = JournalEntry::where('reference_type', 'retention_release')
            ->where('reference_id', $assignment->id)
            ->first();

        expect($je)->not->toBeNull();
    });

    it('validates amount and stage', function () {
        $project = Project::factory()->create();
        $sub = Subcontractor::factory()->create();
        $assignment = makeSubconAssignment($project, $sub);

        postJson('/api/v1/project-subcontractors/'.$assignment->id.'/release-retention', [
            'amount' => 0,
            'stage' => 'cc',
        ], $this->ctx['headers'])->assertStatus(422);

        postJson('/api/v1/project-subcontractors/'.$assignment->id.'/release-retention', [
            'amount' => 100,
            'stage' => 'bogus',
        ], $this->ctx['headers'])->assertStatus(422);
    });

    it('rejects an amount exceeding available CC retention', function () {
        $project = Project::factory()->create();
        $sub = Subcontractor::factory()->create();
        $assignment = makeSubconAssignment($project, $sub);

        postJson('/api/v1/project-subcontractors/'.$assignment->id.'/release-retention', [
            'amount' => 2000,
            'stage' => 'cc',
        ], $this->ctx['headers'])->assertStatus(422);
    });

});
