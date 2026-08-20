<?php

use App\Models\LeaveApplication;
use App\Models\Project;
use App\Models\ProjectClaim;
use App\Models\StaffProfile;
use App\Models\Timecard;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

/**
 * Authorization hardening tests.
 *
 * The API runs behind a single auth:sanctum group; role authorization is
 * enforced via route middleware (role:...) and inline controller checks.
 * These tests prove that a plain `staff` user (who passes auth:sanctum)
 * is BLOCKED (403) from admin, finance, payroll, and cross-user operations,
 * while the intended roles succeed.
 */
function makeAuthUser(string $role = 'staff'): array
{
    $email = 'auth_'.$role.'_'.uniqid().'@example.com';
    $user = User::factory()->create(['email' => $email]);
    $user->syncRoles([$role]);
    $staff = StaffProfile::factory()->create(['email' => $email]);

    return [
        'user' => $user,
        'staff' => $staff,
        'headers' => ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken],
    ];
}

describe('Authorization: staff is blocked from privileged operations', function () {

    it('blocks staff from user management (role:super_admin)', function () {
        $staff = makeAuthUser('staff');
        getJson('/api/v1/users', $staff['headers'])->assertStatus(403);
        postJson('/api/v1/users', ['name' => 'x', 'email' => 'x@x.com', 'password' => 'secret'], $staff['headers'])->assertStatus(403);
        postJson('/api/v1/users/1/roles', ['roles' => ['super_admin']], $staff['headers'])->assertStatus(403);
    });

    it('allows super_admin user management', function () {
        $sa = makeAuthUser('super_admin');
        getJson('/api/v1/users', $sa['headers'])->assertStatus(200);
    });

    it('blocks staff from creating staff (role:admin,super_admin)', function () {
        $staff = makeAuthUser('staff');
        postJson('/api/v1/staff', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'secret',
            'roles' => ['super_admin'],
        ], $staff['headers'])->assertStatus(403);
    });

    it('blocks admin from assigning the super_admin role (defense in depth)', function () {
        $admin = makeAuthUser('admin');
        $target = makeAuthUser('staff');
        putJson('/api/v1/staff/'.$target['staff']->id, ['roles' => ['super_admin']], $admin['headers'])
            ->assertStatus(403);
    });

    it('blocks staff from finance invoice writes', function () {
        $staff = makeAuthUser('staff');
        postJson('/api/v1/invoices', ['client_id' => 1, 'total' => 100], $staff['headers'])->assertStatus(403);
    });

    it('blocks staff from fiscal period closure', function () {
        $staff = makeAuthUser('staff');
        postJson('/api/v1/fiscal-periods', ['name' => 'P', 'start_date' => '2026-01-01', 'end_date' => '2026-01-31'], $staff['headers'])->assertStatus(403);
    });

    it('blocks staff from payroll period creation', function () {
        $staff = makeAuthUser('staff');
        postJson('/api/v1/payroll/periods', [], $staff['headers'])->assertStatus(403);
    });

    it('blocks staff from payment mark-paid', function () {
        $staff = makeAuthUser('staff');
        postJson('/api/v1/payments/mark-paid', [], $staff['headers'])->assertStatus(403);
    });

    it('blocks staff from board summary (company-wide read)', function () {
        $staff = makeAuthUser('staff');
        getJson('/api/v1/board/summary', $staff['headers'])->assertStatus(403);
    });

    it('blocks staff from dashboard summary (company-wide read)', function () {
        $staff = makeAuthUser('staff');
        getJson('/api/v1/dashboard/summary', $staff['headers'])->assertStatus(403);
    });

    it('blocks staff from audit logs', function () {
        $staff = makeAuthUser('staff');
        getJson('/api/v1/audit-logs', $staff['headers'])->assertStatus(403);
    });

    it('blocks staff from payroll reads (salary/bank data)', function () {
        $staff = makeAuthUser('staff');
        getJson('/api/v1/payroll/periods', $staff['headers'])->assertStatus(403);
    });

    it('blocks staff from company settings writes', function () {
        $staff = makeAuthUser('staff');
        putJson('/api/v1/settings/company', ['company_name' => 'x'], $staff['headers'])->assertStatus(403);
    });

    it('blocks staff from system test actions', function () {
        $staff = makeAuthUser('staff');
        postJson('/api/v1/system/test-push', [], $staff['headers'])->assertStatus(403);
        getJson('/api/v1/system/diagnostics', $staff['headers'])->assertStatus(403);
    });

    it('blocks staff from leave approval/rejection', function () {
        $staff = makeAuthUser('staff');
        $leave = LeaveApplication::create([
            'staff_id' => $staff['staff']->id,
            'type' => 'annual',
            'start_date' => date('Y-m-d', strtotime('+7 days')),
            'end_date' => date('Y-m-d', strtotime('+7 days')),
            'status' => 'pending',
        ]);
        postJson('/api/v1/leaves/'.$leave->id.'/approve', [], $staff['headers'])->assertStatus(403);
        postJson('/api/v1/leaves/'.$leave->id.'/reject', ['rejection_reason' => 'no'], $staff['headers'])->assertStatus(403);
    });

    it('blocks staff from leave balance adjustment', function () {
        $staff = makeAuthUser('staff');
        postJson('/api/v1/leave-balances/1/adjust', [], $staff['headers'])->assertStatus(403);
    });

    it('blocks staff from project claim mark-paid', function () {
        $staff = makeAuthUser('staff');
        $project = Project::factory()->create(['status' => 'active']);
        $claim = ProjectClaim::create([
            'claim_number' => 'CLM-'.rand(1000, 9999),
            'project_id' => $project->id,
            'title' => 'x',
            'amount' => 1,
            'status' => 'approved',
        ]);
        postJson('/api/v1/project-claims/'.$claim->id.'/mark-paid', [], $staff['headers'])->assertStatus(403);
    });

    it('blocks staff from subcontractor claim mark-paid', function () {
        $staff = makeAuthUser('staff');
        postJson('/api/v1/subcontractor-claims/1/mark-paid', [], $staff['headers'])->assertStatus(403);
    });

    it('blocks staff from timecard rejection', function () {
        $staff = makeAuthUser('staff');
        $tc = Timecard::create([
            'staff_id' => $staff['staff']->id,
            'date' => date('Y-m-d'),
            'hours_worked' => 8,
            'status' => 'pending',
        ]);
        postJson('/api/v1/timecards/'.$tc->id.'/reject', [], $staff['headers'])->assertStatus(403);
    });

    it('blocks staff from e-invoice settings writes', function () {
        $staff = makeAuthUser('staff');
        putJson('/api/v1/einvoice/settings', [], $staff['headers'])->assertStatus(403);
    });

    it('allows finance on finance gates', function () {
        $finance = makeAuthUser('finance');
        getJson('/api/v1/board/summary', $finance['headers'])->assertStatus(200);
        getJson('/api/v1/payroll/periods', $finance['headers'])->assertStatus(200);
    });

    it('allows hr on payroll gate', function () {
        $hr = makeAuthUser('hr');
        getJson('/api/v1/payroll/periods', $hr['headers'])->assertStatus(200);
    });

    it('blocks staff from reading another staff leave via show (ownership)', function () {
        $owner = makeAuthUser('staff');
        $other = makeAuthUser('staff');
        $leave = LeaveApplication::create([
            'staff_id' => $owner['staff']->id,
            'type' => 'annual',
            'start_date' => date('Y-m-d', strtotime('+10 days')),
            'end_date' => date('Y-m-d', strtotime('+10 days')),
            'status' => 'pending',
        ]);
        getJson('/api/v1/leaves/'.$leave->id, $other['headers'])->assertStatus(403);
    });

    it('allows staff to read their own leave via show (ownership)', function () {
        $owner = makeAuthUser('staff');
        $leave = LeaveApplication::create([
            'staff_id' => $owner['staff']->id,
            'type' => 'annual',
            'start_date' => date('Y-m-d', strtotime('+10 days')),
            'end_date' => date('Y-m-d', strtotime('+10 days')),
            'status' => 'pending',
        ]);
        getJson('/api/v1/leaves/'.$leave->id, $owner['headers'])->assertStatus(200);
    });

});

describe('Authorization: PM cannot access payroll payments', function () {

    it('excludes payroll items from payments/pending for pm', function () {
        $pm = makeAuthUser('pm');
        $res = getJson('/api/v1/payments/pending?type=payroll', $pm['headers'])
            ->assertStatus(200);
        $res->assertJsonCount(0, 'data');
        $res->assertJsonMissingPath('summary.payroll');
    });

    it('blocks pm from marking a payroll item paid', function () {
        $pm = makeAuthUser('pm');
        postJson('/api/v1/payments/mark-paid', ['type' => 'payroll', 'id' => 1], $pm['headers'])
            ->assertStatus(403);
    });

    it('allows pm to see subcon-claim payments (their domain)', function () {
        $pm = makeAuthUser('pm');
        getJson('/api/v1/payments/pending?type=subcon-claim', $pm['headers'])
            ->assertStatus(200);
    });

    it('allows hr to see payroll payments', function () {
        $hr = makeAuthUser('hr');
        getJson('/api/v1/payments/pending?type=payroll', $hr['headers'])
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });

});
