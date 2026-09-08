<?php

use App\Models\EisContributionTier;
use App\Models\EPFSchedule;
use App\Models\PayrollAdjustment;
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

    it('bases EIS/SOCSO on wages only, EPF on wages plus bonus', function () {
        // Boundary tiers so a bonus crossing into tier 2 is observable.
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

        $staff = makePayrollStaff(['basic_salary' => 4000]);
        [$period] = runPayroll();

        $item = payrollItemFor($staff, $period);
        expect($item->socso_employee)->toBe(37.0);
        expect($item->eis_employee)->toBe(8.0);
        expect($item->epf_employee)->toBe(80.0); // FLAT 2% on 4,000

        // Attach a 1,000 bonus adjustment and reprocess (the reprocess flow).
        PayrollAdjustment::create([
            'payroll_run_item_id' => $item->id,
            'type' => 'earnings',
            'label' => 'Bonus',
            'amount' => 1000,
        ]);
        (new PayrollProcessor)->process($period->id);

        $item->refresh();
        // SOCSO/EIS stay on the 4,000 wage — bonus excluded.
        expect($item->socso_employee)->toBe(37.0);
        expect($item->eis_employee)->toBe(8.0);
        // EPF includes the bonus: FLAT 2% on 5,000.
        expect($item->epf_employee)->toBe(100.0);
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

describe('PayrollProcessor PCB', function () {

    it('populates PCB, zakat, and tax-year snapshot on processed items', function () {
        $staff = makePayrollStaff(['zakat_monthly' => 50]);
        [$period] = runPayroll();

        $item = payrollItemFor($staff, $period);

        expect($item)->not->toBeNull();
        expect($item->pcb_employee)->toBeGreaterThan(0);
        expect($item->zakat)->toBe(50.0);
        expect($item->pcb_tax_year)->toBe((int) $period->year);
        expect($item->pcb_method)->toBeArray()
            ->toHaveKeys(['worker_category', 'annual_chargeable', 'remaining_months']);
    });

    it('stores the same PCB across process re-runs (idempotent)', function () {
        $staff = makePayrollStaff();
        [$period] = runPayroll();

        $first = payrollItemFor($staff, $period);
        (new PayrollProcessor)->process($period->id);
        $second = payrollItemFor($staff, $period);

        expect($second->id)->toBe($first->id);
        expect($second->pcb_employee)->toBe($first->pcb_employee);
    });

    it('zeroes PCB when pcb_contributing is disabled for the staff', function () {
        $staff = makePayrollStaff(['pcb_contributing' => false]);
        [$period] = runPayroll();

        $item = payrollItemFor($staff, $period);

        expect($item->pcb_employee)->toBe(0.0);
        expect($item->zakat)->toBe(0.0);
        expect($item->pcb_tax_year)->toBeNull();
        expect($item->pcb_method)->toBeArray()
            ->toHaveKey('pcb_contributing')
            ->and($item->pcb_method['pcb_contributing'])->toBeFalse();
    });

    it('calculates PCB by default when pcb_contributing is unset', function () {
        $staff = makePayrollStaff();

        [$period] = runPayroll();

        $item = payrollItemFor($staff, $period);

        expect($item->pcb_employee)->toBeGreaterThan(0);
    });

    it('applies employer-borne PCB only for periods ending on or before pcb_borne_until', function () {
        $future = makePayrollStaff(['pcb_borne_by_employer' => true, 'pcb_borne_until' => '2030-12-31']);
        $past = makePayrollStaff(['pcb_borne_by_employer' => true, 'pcb_borne_until' => '2020-01-01']);

        [$period] = runPayroll();

        expect(payrollItemFor($future, $period)->pcb_method['pcb_borne_by_employer'])->toBeTrue();
        expect(payrollItemFor($past, $period)->pcb_method['pcb_borne_by_employer'])->toBeFalse();
    });

    it('spikes PCB in the bonus period then returns to baseline, and spikes again for a second bonus', function () {
        $staff = makePayrollStaff();
        $year = 2027;
        $mk = fn (int $m, string $tag) => PayrollPeriod::factory()->open()->create([
            'code' => "SPIKE-{$tag}-{$m}",
            'start_date' => "{$year}-".str_pad((string) $m, 2, '0', STR_PAD_LEFT).'-01',
            'end_date' => "{$year}-".str_pad((string) $m, 2, '0', STR_PAD_LEFT).'-28',
            'month' => $m,
            'year' => $year,
        ]);

        $apr = $mk(4, 'A');
        $may = $mk(5, 'A');
        $jun = $mk(6, 'A');

        // Baseline months first (Jan-Mar) so the bonus period has prior months.
        $jan = $mk(1, 'A');
        $feb = $mk(2, 'A');
        $mar = $mk(3, 'A');
        foreach ([$jan, $feb, $mar] as $p) {
            (new PayrollProcessor)->process($p->id);
        }
        $baseline = payrollItemFor($staff, $mar)->pcb_employee;

        // April: add bonus 5000, reprocess → PCB spikes above baseline.
        (new PayrollProcessor)->process($apr->id);
        $itemApr = payrollItemFor($staff, $apr);
        PayrollAdjustment::create([
            'payroll_run_item_id' => $itemApr->id,
            'type' => 'earnings',
            'statutory_type' => 'additional',
            'label' => 'Bonus',
            'amount' => 5000,
        ]);
        (new PayrollProcessor)->process($apr->id);
        $itemApr->refresh();

        expect($itemApr->pcb_employee)->toBeGreaterThan($baseline);
        expect((float) $itemApr->pcb_method['bonus_tax'])->toBeGreaterThan(0);

        // May: no new bonus → PCB back to baseline (NOT zero).
        (new PayrollProcessor)->process($may->id);
        $itemMay = payrollItemFor($staff, $may);
        expect($itemMay->pcb_employee)->toBe($baseline);

        // June: second bonus 3000 → spikes again above baseline.
        (new PayrollProcessor)->process($jun->id);
        $itemJun = payrollItemFor($staff, $jun);
        PayrollAdjustment::create([
            'payroll_run_item_id' => $itemJun->id,
            'type' => 'earnings',
            'statutory_type' => 'additional',
            'label' => 'Bonus',
            'amount' => 3000,
        ]);
        (new PayrollProcessor)->process($jun->id);
        $itemJun->refresh();

        expect($itemJun->pcb_employee)->toBeGreaterThan($baseline);
        // Second, smaller bonus → smaller incremental spike than April's.
        expect($itemJun->pcb_employee)->toBeLessThan($itemApr->pcb_employee);
        expect((float) $itemJun->pcb_method['bonus_tax'])->toBeGreaterThan(0);
    });

});
