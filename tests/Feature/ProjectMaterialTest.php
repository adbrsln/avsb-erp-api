<?php

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Project;
use App\Models\ProjectMaterialUsage;
use App\Models\StaffProfile;
use App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

function makeMaterialUser(string $role = 'admin'): array
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
    $this->ctx = makeMaterialUser('admin');
});

describe('Project Materials CRUD', function () {

    it('lists material usage for a member', function () {
        $project = Project::factory()->create();
        $item = InventoryItem::factory()->create();
        ProjectMaterialUsage::create([
            'project_id' => $project->id,
            'item_id' => $item->id,
            'qty' => 5,
            'unit_cost' => 10,
            'total_cost' => 50,
        ]);

        getJson('/api/v1/projects/'.$project->id.'/materials', $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    it('forbids non-members from listing materials', function () {
        $project = Project::factory()->create();
        $staff = makeMaterialUser('staff');

        getJson('/api/v1/projects/'.$project->id.'/materials', $staff['headers'])
            ->assertStatus(403);
    });

    it('issues material and decrements stock', function () {
        $project = Project::factory()->create();
        $item = InventoryItem::factory()->create(['stock_qty' => 100, 'unit_cost' => 12.5]);

        postJson('/api/v1/projects/'.$project->id.'/materials', [
            'item_id' => $item->id,
            'qty' => 4,
        ], $this->ctx['headers'])
            ->assertStatus(201)
            ->assertJsonPath('total_cost', 50);

        expect($item->fresh()->stock_qty)->toBe(96.0);

        $tx = InventoryTransaction::where('item_id', $item->id)
            ->where('type', 'out')
            ->where('reference_type', 'material_usage')
            ->first();

        expect($tx)->not->toBeNull();
    });

    it('requires item_id and qty', function () {
        $project = Project::factory()->create();
        $item = InventoryItem::factory()->create();

        postJson('/api/v1/projects/'.$project->id.'/materials', ['item_id' => $item->id], $this->ctx['headers'])
            ->assertStatus(422);

        postJson('/api/v1/projects/'.$project->id.'/materials', ['qty' => 1], $this->ctx['headers'])
            ->assertStatus(422);
    });

    it('rejects non-positive qty', function () {
        $project = Project::factory()->create();
        $item = InventoryItem::factory()->create();

        postJson('/api/v1/projects/'.$project->id.'/materials', ['item_id' => $item->id, 'qty' => 0], $this->ctx['headers'])
            ->assertStatus(422);
    });

    it('reverses material usage and restores stock on delete', function () {
        $project = Project::factory()->create();
        $item = InventoryItem::factory()->create(['stock_qty' => 50, 'unit_cost' => 5]);
        $usage = ProjectMaterialUsage::create([
            'project_id' => $project->id,
            'item_id' => $item->id,
            'qty' => 10,
            'unit_cost' => 5,
            'total_cost' => 50,
        ]);

        deleteJson('/api/v1/project-materials/'.$usage->id, [], $this->ctx['headers'])
            ->assertStatus(204);

        expect($item->fresh()->stock_qty)->toBe(60.0);
        expect(ProjectMaterialUsage::find($usage->id))->toBeNull();
    });

    it('forbids staff from deleting material usage', function () {
        $project = Project::factory()->create();
        $item = InventoryItem::factory()->create();
        $usage = ProjectMaterialUsage::create([
            'project_id' => $project->id,
            'item_id' => $item->id,
            'qty' => 1,
            'unit_cost' => 1,
            'total_cost' => 1,
        ]);
        $staff = makeMaterialUser('staff');

        deleteJson('/api/v1/project-materials/'.$usage->id, [], $staff['headers'])
            ->assertStatus(403);
    });

});
