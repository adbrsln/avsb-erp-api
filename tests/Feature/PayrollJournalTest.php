<?php

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use App\Models\StaffProfile;
use App\Services\Payroll\PayrollJournalService;

beforeEach(function () {
    $this->period = PayrollPeriod::factory()->open()->create();
    $this->staff = StaffProfile::factory()->create(['basic_salary' => 4000]);
});

function makePaidItem(PayrollPeriod $period, StaffProfile $staff, array $overrides = []): PayrollRunItem
{
    return PayrollRunItem::factory()->create(array_merge([
        'period_id' => $period->id,
        'employee_id' => $staff->id,
        'salary' => 4000,
        'epf_employer' => 480,
        'epf_employee' => 440,
        'epf_schedule_code' => 'FLAT',
        'socso_employer' => 84,
        'socso_employee' => 37,
        'eis_employer' => 21,
        'eis_employee' => 8,
        'socso_24h_employee' => 0,
        'pcb_employee' => 46.80,
        'zakat' => 50,
        'paid' => true,
        'confirmed' => true,
    ], $overrides));
}

it('posts a balanced payroll journal with statutory + PCB + zakat lines', function () {
    $item = makePaidItem($this->period, $this->staff);

    (new PayrollJournalService)->post($item);

    $je = JournalEntry::where('reference_type', 'payroll')->where('reference_id', $item->id)->first();
    expect($je)->not->toBeNull();
    expect($je->status)->toBe('posted');
    expect($je->lines)->toHaveCount(10);
    expect(round($je->lines->sum('debit'), 2))->toBe(round($je->lines->sum('credit'), 2));
});

it('is idempotent — re-posting replaces the existing journal', function () {
    $item = makePaidItem($this->period, $this->staff);

    $service = new PayrollJournalService;
    $service->post($item);
    $service->post($item);

    expect(JournalEntry::where('reference_type', 'payroll')->where('reference_id', $item->id)->count())->toBe(1);
});

it('posts a simple two-line journal for part-time items', function () {
    $item = makePaidItem($this->period, $this->staff, [
        'wage_type' => 'hourly_timesheet',
        'salary' => 1500,
        'epf_employer' => 0,
        'epf_employee' => 0,
        'socso_employer' => 0,
        'socso_employee' => 0,
        'eis_employer' => 0,
        'eis_employee' => 0,
        'pcb_employee' => 0,
        'zakat' => 0,
    ]);

    (new PayrollJournalService)->post($item);

    $je = JournalEntry::where('reference_type', 'payroll')->where('reference_id', $item->id)->first();
    expect($je->lines)->toHaveCount(2);
    expect($je->lines->sum('debit'))->toBe($je->lines->sum('credit'));
});

it('folds SOCSO-24h into the SOCSO payable credit', function () {
    $item = makePaidItem($this->period, $this->staff, ['socso_24h_employee' => 100]);

    (new PayrollJournalService)->post($item);

    $je = JournalEntry::where('reference_type', 'payroll')->where('reference_id', $item->id)->first();
    $socsoLine = $je->lines->firstWhere('account_id', ChartOfAccount::where('code', '2104')->value('id'));
    expect($socsoLine->credit)->toBe(37.0 + 84.0 + 100.0);
});

it('credits net pay to the configured payroll bank account', function () {
    $item = makePaidItem($this->period, $this->staff);
    $bankId = ChartOfAccount::where('code', '1102')->value('id');

    (new PayrollJournalService)->post($item);

    $je = JournalEntry::where('reference_type', 'payroll')->where('reference_id', $item->id)->first();
    $bankLine = $je->lines->firstWhere('account_id', $bankId);
    expect($bankLine)->not->toBeNull();
    expect(round($bankLine->credit, 2))->toBe(round($item->net_pay, 2));
});

it('treats employer-borne PCB as salary expense and keeps net pay un-reduced', function () {
    $item = makePaidItem($this->period, $this->staff, [
        'pcb_employee' => 46.80,
        'pcb_method' => ['pcb_borne_by_employer' => true],
    ]);

    // net_pay excludes PCB when employer-borne
    expect(round($item->net_pay, 2))->toBe(round(4000 - 440 - 37 - 8 - 50, 2));

    (new PayrollJournalService)->post($item);

    $je = JournalEntry::where('reference_type', 'payroll')->where('reference_id', $item->id)->first();
    expect($je->lines)->toHaveCount(11);
    expect(round($je->lines->sum('debit'), 2))->toBe(round($je->lines->sum('credit'), 2));

    $salaryAcct = ChartOfAccount::where('code', '6101')->value('id');
    $salaryDebit = $je->lines->where('account_id', $salaryAcct)->sum('debit');
    expect(round($salaryDebit, 2))->toBe(round(4000 + 46.80, 2));
});

it('throws without leaving a partial journal when an account is missing', function () {
    $item = makePaidItem($this->period, $this->staff);
    ChartOfAccount::where('code', '2103')->delete();

    try {
        (new PayrollJournalService)->post($item);
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain('2103');
    }

    expect(JournalEntry::where('reference_type', 'payroll')->where('reference_id', $item->id)->count())->toBe(0);
});
