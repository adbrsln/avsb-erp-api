<?php

use App\Models\EisContributionTier;
use App\Models\EPFSchedule;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use App\Models\SocsoContributionTier;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\Payroll\PayrollProcessor;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    // EPF: ScheduleDeterminer falls back to FLAT when no EPFScheduleRule rows exist
    EPFSchedule::firstOrCreate(
        ['code' => 'FLAT'],
        ['name' => 'Flat', 'employer_rate' => 12, 'employee_rate' => 11, 'max_tier_wage' => 0],
    );

    // SOCSO tier covering the test salary (4000.00 and adjusted 4100.00)
    SocsoContributionTier::create([
        'wage_from' => 0.01,
        'wage_to' => 10000.00,
        'employer_amount' => 84.00,
        'employee_amount' => 37.00,
    ]);

    // EIS tier covering the same range
    EisContributionTier::create([
        'wage_from' => 0.01,
        'wage_to' => 10000.00,
        'employer_amount' => 21.00,
        'employee_amount' => 8.00,
    ]);

    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

function makePayrollStaff(array $overrides = []): StaffProfile
{
    return StaffProfile::factory()->create(array_merge([
        'basic_salary' => 4000,
        'is_active' => true,
        'citizenship' => 'non_citizen',
        'nationality' => 'Foreign',
        'has_pr' => false,
        'epf_member_before_aug_1998' => false,
        'epf_contributing' => true,
        'socso_contributing' => true,
        'eis_contributing' => true,
        'socso_24h_enabled' => false,
    ], $overrides));
}

function runPayroll(): array
{
    $period = PayrollPeriod::factory()->open()->create();
    (new PayrollProcessor)->process($period->id);

    return [$period, PayrollRunItem::where('period_id', $period->id)];
}

function payrollItemFor(StaffProfile $staff, PayrollPeriod $period): PayrollRunItem
{
    return PayrollRunItem::where('period_id', $period->id)
        ->where('employee_id', $staff->id)
        ->first();
}

describe('PayrollProcessor statutory opt-outs', function () {

    it('applies EPF and SOCSO by default for contributing staff', function () {
        $staff = makePayrollStaff();
        [$period] = runPayroll();

        $item = payrollItemFor($staff, $period);

        expect($item)->not->toBeNull();
        expect($item->salary)->toBe(4000.0);
        // EPF FLAT 2% / 2% (non-citizen, not elected)
        expect($item->epf_employer)->toBe(80.0);
        expect($item->epf_employee)->toBe(80.0);
        expect($item->epf_schedule_code)->toBe('FLAT');
        // SOCSO tier 84 / 37
        expect($item->socso_employer)->toBe(84.0);
        expect($item->socso_employee)->toBe(37.0);
        // EIS tier 21 / 8
        expect($item->eis_employer)->toBe(21.0);
        expect($item->eis_employee)->toBe(8.0);
    });

    it('includes non-EPF-contributing staff and zeroes their EPF', function () {
        $staff = makePayrollStaff(['epf_contributing' => false]);
        [$period] = runPayroll();

        $item = payrollItemFor($staff, $period);

        expect($item)->not->toBeNull();
        expect($item->salary)->toBe(4000.0);
        expect($item->epf_employer)->toBe(0.0);
        expect($item->epf_employee)->toBe(0.0);
        // determined schedule kept (FK constraint), amounts zeroed
        expect($item->epf_schedule_code)->toBe('FLAT');
        // SOCSO still applies
        expect($item->socso_employer)->toBe(84.0);
        expect($item->socso_employee)->toBe(37.0);
    });

    it('zeroes SOCSO when socso_contributing is disabled', function () {
        $staff = makePayrollStaff(['socso_contributing' => false]);
        [$period] = runPayroll();

        $item = payrollItemFor($staff, $period);

        expect($item)->not->toBeNull();
        expect($item->socso_employer)->toBe(0.0);
        expect($item->socso_employee)->toBe(0.0);
        // EPF still applies
        expect($item->epf_employer)->toBe(80.0);
        expect($item->epf_employee)->toBe(80.0);
    });

    it('zeroes EIS when eis_contributing is disabled', function () {
        $staff = makePayrollStaff(['eis_contributing' => false]);
        [$period] = runPayroll();

        $item = payrollItemFor($staff, $period);

        expect($item)->not->toBeNull();
        expect($item->eis_employer)->toBe(0.0);
        expect($item->eis_employee)->toBe(0.0);
        // EPF and SOCSO still apply
        expect($item->epf_employer)->toBe(80.0);
        expect($item->socso_employer)->toBe(84.0);
    });

    it('skips SKBBK (Socso 24h) when socso_contributing is disabled', function () {
        $staff = makePayrollStaff([
            'socso_contributing' => false,
            'socso_24h_enabled' => true,
            'socso_category' => 'first',
        ]);
        [$period] = runPayroll();

        $item = payrollItemFor($staff, $period);

        expect($item)->not->toBeNull();
        expect($item->socso_24h_employee)->toBe(0.0);
    });

    it('zeroes all statutory deductions when EPF, SOCSO and EIS are disabled', function () {
        $staff = makePayrollStaff([
            'epf_contributing' => false,
            'socso_contributing' => false,
            'eis_contributing' => false,
        ]);
        [$period] = runPayroll();

        $item = payrollItemFor($staff, $period);

        expect($item)->not->toBeNull();
        expect($item->salary)->toBe(4000.0);
        expect($item->epf_employer)->toBe(0.0);
        expect($item->epf_employee)->toBe(0.0);
        expect($item->socso_employer)->toBe(0.0);
        expect($item->socso_employee)->toBe(0.0);
        expect($item->eis_employer)->toBe(0.0);
        expect($item->eis_employee)->toBe(0.0);
    });

    it('keeps statutory zeroed when recalculating after an earnings adjustment', function () {
        $staff = makePayrollStaff([
            'epf_contributing' => false,
            'socso_contributing' => false,
            'eis_contributing' => false,
        ]);
        $period = PayrollPeriod::factory()->open()->create();
        $item = PayrollRunItem::factory()->create([
            'period_id' => $period->id,
            'employee_id' => $staff->id,
            'salary' => 4000,
            'epf_employer' => 0,
            'epf_employee' => 0,
            'epf_schedule_code' => 'FLAT',
            'socso_employer' => 0,
            'socso_employee' => 0,
            'eis_employer' => 0,
            'eis_employee' => 0,
        ]);

        postJson("/api/v1/payroll/periods/{$period->id}/items/{$item->id}/adjustments", [
            'type' => 'earnings',
            'label' => 'Overtime',
            'amount' => 200,
        ], $this->headers)->assertStatus(201);

        $item->refresh();

        expect($item->epf_employer)->toBe(0.0);
        expect($item->epf_employee)->toBe(0.0);
        expect($item->socso_employer)->toBe(0.0);
        expect($item->socso_employee)->toBe(0.0);
        expect($item->eis_employer)->toBe(0.0);
        expect($item->eis_employee)->toBe(0.0);
    });

    it('recalculates statutory from staff flags after an earnings adjustment', function () {
        $staff = makePayrollStaff(); // all three contributing
        $period = PayrollPeriod::factory()->open()->create();
        $item = PayrollRunItem::factory()->create([
            'period_id' => $period->id,
            'employee_id' => $staff->id,
            'salary' => 4000,
            'epf_schedule_code' => 'FLAT',
        ]);

        postJson("/api/v1/payroll/periods/{$period->id}/items/{$item->id}/adjustments", [
            'type' => 'earnings',
            'label' => 'Overtime',
            'amount' => 100,
        ], $this->headers)->assertStatus(201);

        $item->refresh();

        // adjusted salary = 4100 → EPF FLAT 2%/2%, SOCSO tier 84/37, EIS tier 21/8
        expect($item->epf_employer)->toBe(82.0);
        expect($item->epf_employee)->toBe(82.0);
        expect($item->socso_employer)->toBe(84.0);
        expect($item->socso_employee)->toBe(37.0);
        expect($item->eis_employer)->toBe(21.0);
        expect($item->eis_employee)->toBe(8.0);
    });

    it('returns item statutory in the process result payload', function () {
        $staff = makePayrollStaff(['epf_contributing' => false]);
        $period = PayrollPeriod::factory()->open()->create();

        $result = (new PayrollProcessor)->process($period->id);

        $entry = collect($result['items'])->firstWhere('employee_id', $staff->id);
        expect($entry)->not->toBeNull();
        expect($entry['epf_employer'])->toBe(0.0);
        expect($entry['epf_employee'])->toBe(0.0);
        expect($entry['socso_employer'])->toBe(84.0);
        expect($entry['socso_employee'])->toBe(37.0);
    });

    it('processes, confirms and marks paid staff with EPF contributing disabled', function () {
        $staff = makePayrollStaff(['epf_contributing' => false]);
        $period = PayrollPeriod::factory()->open()->create();

        // Full UI flow: process → item exists → confirm → mark paid (generates payslip)
        postJson("/api/v1/payroll/periods/{$period->id}/process", [], $this->headers)
            ->assertStatus(200);

        $item = payrollItemFor($staff, $period);
        expect($item)->not->toBeNull();
        expect($item->epf_employer)->toBe(0.0);
        expect($item->epf_employee)->toBe(0.0);

        postJson("/api/v1/payroll/periods/{$period->id}/items/{$item->id}/confirm", [], $this->headers)
            ->assertStatus(200);
        postJson("/api/v1/payroll/periods/{$period->id}/items/{$item->id}/mark-paid", [], $this->headers)
            ->assertStatus(200);

        expect($item->refresh()->paid)->toBeTrue();
    });

    it('staff can download their payslip with EPF contributing disabled', function () {
        $staff = makePayrollStaff(['epf_contributing' => false]);
        $period = PayrollPeriod::factory()->open()->create();
        $item = PayrollRunItem::factory()->create([
            'period_id' => $period->id,
            'employee_id' => $staff->id,
            'salary' => 4000,
            'epf_employer' => 0,
            'epf_employee' => 0,
            'epf_schedule_code' => 'FLAT',
            'paid' => true,
            'paid_at' => now(),
            'paid_by' => $staff->id,
            'confirmed' => true,
            'confirmed_at' => now(),
            'confirmed_by' => $staff->id,
        ]);

        $owner = User::factory()->create(['email' => $staff->email]);
        $owner->syncRoles(['staff']);
        $ownerToken = $owner->createToken('test')->plainTextToken;

        getJson('/api/v1/payroll/payslips/'.$item->id.'/download', ['Authorization' => 'Bearer '.$ownerToken])
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    });

});
