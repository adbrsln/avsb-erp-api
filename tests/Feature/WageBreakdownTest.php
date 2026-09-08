<?php

use App\Models\EisContributionTier;
use App\Models\EPFSchedule;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use App\Models\SocsoContributionTier;
use App\Models\StaffAllowance;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\Payroll\PayrollProcessor;
use App\Services\Payroll\WageBreakdown;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    EPFSchedule::firstOrCreate(
        ['code' => 'FLAT'],
        ['name' => 'Flat', 'employer_rate' => 12, 'employee_rate' => 11, 'max_tier_wage' => 0],
    );

    // Replace the seeded real tables with boundary tiers so base crossing
    // into tier 2 is observable.
    SocsoContributionTier::query()->delete();
    EisContributionTier::query()->delete();

    SocsoContributionTier::create([
        'wage_from' => 0.01, 'wage_to' => 4500.00,
        'employer_amount' => 84.00, 'employee_amount' => 37.00,
    ]);
    SocsoContributionTier::create([
        'wage_from' => 4500.01, 'wage_to' => 10000.00,
        'employer_amount' => 120.00, 'employee_amount' => 55.00,
    ]);
    EisContributionTier::create([
        'wage_from' => 0.01, 'wage_to' => 4500.00,
        'employer_amount' => 21.00, 'employee_amount' => 8.00,
    ]);
    EisContributionTier::create([
        'wage_from' => 4500.01, 'wage_to' => 10000.00,
        'employer_amount' => 25.00, 'employee_amount' => 12.00,
    ]);

    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

function wageStaff(array $overrides = []): StaffProfile
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

function wagePeriod(): PayrollPeriod
{
    return PayrollPeriod::factory()->open()->create([
        'code' => 'PR-202606',
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-30',
        'month' => 6,
        'year' => 2026,
    ]);
}

describe('WageBreakdown resolver', function () {
    it('splits allowances into statutory bases by type', function () {
        $staff = wageStaff();
        StaffAllowance::create(['staff_id' => $staff->id, 'name' => 'Housing', 'amount' => 500, 'statutory_type' => 'wages']);
        StaffAllowance::create(['staff_id' => $staff->id, 'name' => 'Annual Bonus', 'amount' => 1000, 'statutory_type' => 'additional']);
        StaffAllowance::create(['staff_id' => $staff->id, 'name' => 'OT', 'amount' => 300, 'statutory_type' => 'overtime']);
        StaffAllowance::create(['staff_id' => $staff->id, 'name' => 'Mileage', 'amount' => 200, 'statutory_type' => 'reimbursement']);

        $b = WageBreakdown::resolve($staff, wagePeriod());

        expect($b->wagesBase)->toBe(4500.0);
        expect($b->epfBase)->toBe(5500.0);
        expect($b->additionalRemuneration)->toBe(1300.0);
        expect($b->reimbursementTotal)->toBe(200.0);
        expect(count($b->allowances))->toBe(4);
    });

    it('excludes allowances outside the effective window', function () {
        $staff = wageStaff();
        StaffAllowance::create([
            'staff_id' => $staff->id, 'name' => 'Future', 'amount' => 999,
            'statutory_type' => 'wages', 'effective_from' => '2027-01-01',
        ]);
        StaffAllowance::create([
            'staff_id' => $staff->id, 'name' => 'Expired', 'amount' => 999,
            'statutory_type' => 'wages', 'effective_to' => '2025-12-31',
        ]);
        StaffAllowance::create([
            'staff_id' => $staff->id, 'name' => 'Current', 'amount' => 300,
            'statutory_type' => 'wages', 'effective_from' => '2026-01-01', 'effective_to' => '2026-12-31',
        ]);

        $b = WageBreakdown::resolve($staff, wagePeriod());

        expect($b->wagesBase)->toBe(4300.0);
        expect(count($b->allowances))->toBe(1);
    });

    it('aggregates earnings adjustments by statutory type', function () {
        $staff = wageStaff();
        $period = wagePeriod();
        (new PayrollProcessor)->process($period->id);
        $item = PayrollRunItem::where('period_id', $period->id)->where('employee_id', $staff->id)->first();

        PayrollAdjustment::create(['payroll_run_item_id' => $item->id, 'type' => 'earnings', 'statutory_type' => 'wages', 'label' => 'Temp Allowance', 'amount' => 200]);
        PayrollAdjustment::create(['payroll_run_item_id' => $item->id, 'type' => 'earnings', 'statutory_type' => 'additional', 'label' => 'Bonus', 'amount' => 300]);

        $b = WageBreakdown::resolve($staff, $period, $item);

        expect($b->wagesBase)->toBe(4200.0);
        expect($b->epfBase)->toBe(4500.0);
        expect($b->additionalRemuneration)->toBe(300.0);
    });
});

describe('PayrollProcessor wage composition', function () {
    it('feeds wages allowance to SOCSO/EIS/EPF and bonus to EPF only', function () {
        $staff = wageStaff();
        StaffAllowance::create(['staff_id' => $staff->id, 'name' => 'Housing', 'amount' => 600, 'statutory_type' => 'wages']);
        $period = wagePeriod();

        (new PayrollProcessor)->process($period->id);
        $item = PayrollRunItem::where('period_id', $period->id)->where('employee_id', $staff->id)->first();

        // wagesBase = 4,600 → tier 2 for SOCSO/EIS; EPF FLAT 2% on 4,600.
        expect($item->socso_employee)->toBe(55.0);
        expect($item->eis_employee)->toBe(12.0);
        expect($item->epf_employee)->toBe(92.0);

        // Attach a bonus (additional) and reprocess — EPF grows, SOCSO/EIS do not.
        PayrollAdjustment::create([
            'payroll_run_item_id' => $item->id, 'type' => 'earnings',
            'statutory_type' => 'additional', 'label' => 'Bonus', 'amount' => 1000,
        ]);
        (new PayrollProcessor)->process($period->id);
        $item->refresh();

        expect($item->epf_employee)->toBe(112.0); // 2% on 5,600
        expect($item->socso_employee)->toBe(55.0); // still on 4,600
        expect($item->eis_employee)->toBe(12.0);
        expect((float) $item->wage_breakdown['wages_base'])->toBe(4600.0);
        expect((float) $item->wage_breakdown['epf_base'])->toBe(5600.0);
        expect((float) $item->wage_breakdown['additional_remuneration'])->toBe(1000.0);
    });

    it('excludes overtime and reimbursement from EPF and SOCSO/EIS', function () {
        $staff = wageStaff();
        StaffAllowance::create(['staff_id' => $staff->id, 'name' => 'OT', 'amount' => 500, 'statutory_type' => 'overtime']);
        StaffAllowance::create(['staff_id' => $staff->id, 'name' => 'Mileage', 'amount' => 300, 'statutory_type' => 'reimbursement']);
        $period = wagePeriod();

        (new PayrollProcessor)->process($period->id);
        $item = PayrollRunItem::where('period_id', $period->id)->where('employee_id', $staff->id)->first();

        expect($item->epf_employee)->toBe(80.0); // 2% on 4,000 only
        expect($item->socso_employee)->toBe(37.0); // tier 1, wages only
        expect($item->eis_employee)->toBe(8.0);
    });
});

describe('Staff allowance API', function () {
    it('manages allowances via CRUD routes', function () {
        $staff = wageStaff();

        $created = postJson('/api/v1/staff/'.$staff->id.'/allowances', [
            'name' => 'Housing', 'amount' => 500, 'statutory_type' => 'wages',
            'effective_from' => '2026-01-01', 'effective_to' => '2026-12-31',
        ], $this->headers)->assertStatus(201)->json('data');

        expect($created['statutory_type'])->toBe('wages');

        $list = getJson('/api/v1/staff/'.$staff->id.'/allowances', $this->headers)->assertOk()->json('data');
        expect(count($list))->toBe(1);

        putJson('/api/v1/staff/'.$staff->id.'/allowances/'.$created['id'], [
            'name' => 'Housing', 'amount' => 600, 'statutory_type' => 'wages',
        ], $this->headers)->assertOk()->assertJsonPath('data.amount', 600);

        deleteJson('/api/v1/staff/'.$staff->id.'/allowances/'.$created['id'], [], $this->headers)->assertOk();
        expect(getJson('/api/v1/staff/'.$staff->id.'/allowances', $this->headers)->json('data'))->toBe([]);
    });

    it('rejects an invalid statutory type', function () {
        $staff = wageStaff();

        postJson('/api/v1/staff/'.$staff->id.'/allowances', [
            'name' => 'Bad', 'amount' => 100, 'statutory_type' => 'bogus',
        ], $this->headers)->assertStatus(422);
    });
});
