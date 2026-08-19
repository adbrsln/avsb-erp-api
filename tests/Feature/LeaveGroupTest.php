<?php

use App\Models\LeaveGroup;
use App\Models\LeaveGroupEntitlement;
use App\Models\StaffLeaveBalance;
use App\Models\StaffProfile;
use App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

function makeLeaveGroupUser(string $role = 'admin'): array
{
    $email = fake()->unique()->safeEmail();
    $user = User::factory()->create(['email' => $email]);
    $user->syncRoles([$role]);

    return [
        'user' => $user,
        'headers' => ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken],
    ];
}

beforeEach(function () {
    $this->ctx = makeLeaveGroupUser('admin');
});

describe('Leave Groups CRUD', function () {

    it('lists leave groups with entitlements', function () {
        $group = LeaveGroup::create(['name' => 'Operations']);
        $group->entitlements()->create(['type' => 'annual', 'label' => 'Annual', 'days_entitled' => 14]);

        getJson('/api/v1/leave-groups', $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Operations');
    });

    it('creates a leave group with entitlements', function () {
        postJson('/api/v1/leave-groups', [
            'name' => 'Field Crew',
            'entitlements' => [
                ['type' => 'annual', 'label' => 'Annual', 'days_entitled' => 12],
            ],
        ], $this->ctx['headers'])
            ->assertStatus(201)
            ->assertJsonPath('name', 'Field Crew')
            ->assertJsonCount(1, 'entitlements');
    });

    it('requires a name', function () {
        postJson('/api/v1/leave-groups', ['description' => 'No name'], $this->ctx['headers'])
            ->assertStatus(422);
    });

    it('shows a leave group', function () {
        $group = LeaveGroup::create(['name' => 'Show me']);

        getJson('/api/v1/leave-groups/'.$group->id, $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('name', 'Show me');
    });

    it('updates a leave group and replaces entitlements', function () {
        $group = LeaveGroup::create(['name' => 'Old']);
        $group->entitlements()->create(['type' => 'annual', 'label' => 'Annual', 'days_entitled' => 10]);

        putJson('/api/v1/leave-groups/'.$group->id, [
            'name' => 'New',
            'entitlements' => [
                ['type' => 'medical', 'label' => 'Medical', 'days_entitled' => 14],
            ],
        ], $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('name', 'New')
            ->assertJsonCount(1, 'entitlements')
            ->assertJsonPath('entitlements.0.type', 'medical');
    });

    it('deletes a leave group', function () {
        $group = LeaveGroup::create(['name' => 'Delete me']);

        deleteJson('/api/v1/leave-groups/'.$group->id, [], $this->ctx['headers'])
            ->assertStatus(204);

        expect(LeaveGroup::find($group->id))->toBeNull();
    });

});

describe('Leave Groups balances', function () {

    it('lists a staff member\'s leave balances by year', function () {
        $staff = StaffProfile::factory()->create();
        StaffLeaveBalance::create([
            'staff_id' => $staff->id,
            'type' => 'annual',
            'year' => date('Y'),
            'entitled' => 14,
            'used' => 3,
            'adjusted' => 0,
            'balance' => 11,
        ]);

        getJson('/api/v1/leave-groups/'.$staff->id.'/entitlements?year='.date('Y'), $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    it('adjusts a leave balance and recomputes balance', function () {
        $staff = StaffProfile::factory()->create();
        $balance = StaffLeaveBalance::create([
            'staff_id' => $staff->id,
            'type' => 'annual',
            'year' => date('Y'),
            'entitled' => 14,
            'used' => 3,
            'adjusted' => 0,
            'balance' => 11,
        ]);

        postJson('/api/v1/leave-balances/'.$balance->id.'/adjust', ['adjusted' => 2], $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonPath('adjusted', 2)
            ->assertJsonPath('balance', 13);
    });

    it('deletes a leave group entitlement', function () {
        $group = LeaveGroup::create(['name' => 'Entitled']);
        $entitlement = $group->entitlements()->create(['type' => 'annual', 'label' => 'Annual', 'days_entitled' => 14]);

        deleteJson('/api/v1/leave-group-entitlements/'.$entitlement->id, [], $this->ctx['headers'])
            ->assertStatus(204);

        expect(LeaveGroupEntitlement::find($entitlement->id))->toBeNull();
    });

    it('carries forward unused leave days to the next year', function () {
        $staff = StaffProfile::factory()->create();
        $fromYear = date('Y') - 1;
        $toYear = date('Y');
        StaffLeaveBalance::create([
            'staff_id' => $staff->id,
            'type' => 'annual',
            'year' => $fromYear,
            'entitled' => 14,
            'used' => 4,
            'adjusted' => 0,
            'balance' => 10,
        ]);

        postJson('/api/v1/staff/'.$staff->id.'/carry-forward', [
            'from_year' => $fromYear,
            'to_year' => $toYear,
            'items' => [
                ['type' => 'annual', 'days' => 5],
            ],
        ], $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.balance', 5);

        $from = StaffLeaveBalance::where('staff_id', $staff->id)->where('year', $fromYear)->first();
        expect((float) $from->balance)->toBe(5.0);
    });

});
