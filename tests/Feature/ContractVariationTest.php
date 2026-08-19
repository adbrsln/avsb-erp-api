<?php

use App\Models\Contract;
use App\Models\ContractVariation;
use App\Models\StaffProfile;
use App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

function makeVariationUser(string $role = 'admin'): array
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

function makeContract(): Contract
{
    return Contract::factory()->create([
        'status' => 'active',
        'subtotal' => 1000,
        'total_amount' => 1080,
        'sst_rate' => 8,
    ]);
}

function makeVariation(Contract $contract, array $overrides = []): ContractVariation
{
    return ContractVariation::create(array_merge([
        'contract_id' => $contract->id,
        'variation_number' => 'VO-'.rand(1000, 9999),
        'description' => 'Extra works',
        'amount' => 100,
        'status' => 'pending',
    ], $overrides));
}

beforeEach(function () {
    $this->ctx = makeVariationUser('admin');
});

describe('Contract Variations CRUD', function () {

    it('lists variations with total amount', function () {
        $contract = makeContract();
        makeVariation($contract, ['amount' => 100]);
        makeVariation($contract, ['amount' => 50]);

        getJson('/api/v1/contracts/'.$contract->id.'/variations', $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('total_variation_amount', 150);
    });

    it('creates a pending variation with generated number', function () {
        $contract = makeContract();

        postJson('/api/v1/contracts/'.$contract->id.'/variations', [
            'description' => 'Additional asphalt',
            'amount' => 200,
        ], $this->ctx['headers'])
            ->assertStatus(201)
            ->assertJsonPath('status', 'pending')
            ->assertJsonStructure(['variation_number', 'id']);
    });

    it('requires description', function () {
        $contract = makeContract();

        postJson('/api/v1/contracts/'.$contract->id.'/variations', ['amount' => 100], $this->ctx['headers'])
            ->assertStatus(422);
    });

    it('shows a variation scoped to its contract', function () {
        $contract = makeContract();
        $variation = makeVariation($contract);

        getJson('/api/v1/contracts/'.$contract->id.'/variations/'.$variation->id, $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('id', $variation->id);
    });

    it('updates a pending variation', function () {
        $contract = makeContract();
        $variation = makeVariation($contract);

        putJson('/api/v1/contracts/'.$contract->id.'/variations/'.$variation->id, ['description' => 'Updated', 'amount' => 300], $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('description', 'Updated');
    });

    it('blocks editing a non-pending variation', function () {
        $contract = makeContract();
        $variation = makeVariation($contract, ['status' => 'approved']);

        putJson('/api/v1/contracts/'.$contract->id.'/variations/'.$variation->id, ['description' => 'Nope'], $this->ctx['headers'])
            ->assertStatus(422);
    });

    it('deletes a pending variation', function () {
        $contract = makeContract();
        $variation = makeVariation($contract);

        deleteJson('/api/v1/contracts/'.$contract->id.'/variations/'.$variation->id, [], $this->ctx['headers'])
            ->assertStatus(204);

        expect(ContractVariation::find($variation->id))->toBeNull();
    });

});

describe('Contract Variations approval', function () {

    it('approves a variation and updates the contract total', function () {
        $contract = makeContract();
        $variation = makeVariation($contract, ['amount' => 200]);

        postJson('/api/v1/contracts/'.$contract->id.'/variations/'.$variation->id.'/approve', [], $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'approved');

        expect((float) $contract->fresh()->total_amount)->toBe(1280.0);
    });

    it('rejects a variation', function () {
        $contract = makeContract();
        $variation = makeVariation($contract);

        postJson('/api/v1/contracts/'.$contract->id.'/variations/'.$variation->id.'/reject', [], $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'rejected');
    });

    it('blocks approving an already-decided variation', function () {
        $contract = makeContract();
        $variation = makeVariation($contract, ['status' => 'rejected']);

        postJson('/api/v1/contracts/'.$contract->id.'/variations/'.$variation->id.'/approve', [], $this->ctx['headers'])
            ->assertStatus(422);
    });

});
