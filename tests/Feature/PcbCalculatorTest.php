<?php

use App\Services\Payroll\PcbCalculator;

/**
 * Golden vectors hand-computed from the LHDN computerised MTD method
 * (resident progressive rates YA 2026). To be re-validated against
 * calcpcbplus.hasil.gov.my via the validation harness.
 */
function pcbRound(float $n): float
{
    return round($n * 20) / 20;
}

it('returns zero PCB when annual chargeable income is within the 0% band', function () {
    $pcb = (new PcbCalculator)->calculateRaw(
        monthlyGross: 1000,
        employeeEpf: 0,
        taxYear: 2026,
        workerCategory: 'pemastautin',
        maritalStatus: 'single',
        spouseWorking: null,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'normal',
        month: 1,
    );

    expect($pcb->amount)->toBe(0.0);
});

it('matches LHDN on a 5-sen boundary (RM5,470 single + EPF → 149.20, live-verified)', function () {
    $pcb = (new PcbCalculator)->calculateRaw(
        monthlyGross: 5470,
        employeeEpf: 601.70,
        taxYear: 2026,
        workerCategory: 'pemastautin',
        maritalStatus: 'single',
        spouseWorking: null,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'normal',
        month: 1,
    );

    expect($pcb->amount)->toBe(149.20);
});

it('calculates PCB for a single resident (RM8,000, EPF 11%) — live-verified 514.20', function () {
    $pcb = (new PcbCalculator)->calculateRaw(
        monthlyGross: 8000,
        employeeEpf: 880,
        taxYear: 2026,
        workerCategory: 'pemastautin',
        maritalStatus: 'single',
        spouseWorking: null,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'normal',
        month: 1,
    );

    expect($pcb->amount)->toBe(514.20);
    expect($pcb->chargeableIncome)->toBe(83000.0);
});

it('applies spouse + child relief for married KA2 with two children', function () {
    $pcb = (new PcbCalculator)->calculateRaw(
        monthlyGross: 8000,
        employeeEpf: 880,
        taxYear: 2026,
        workerCategory: 'pemastautin',
        maritalStatus: 'married',
        spouseWorking: false,
        spouseDisabled: false,
        childrenTax: ['a' => ['total' => 2, 'eligible_50' => 0]],
        abilityStatus: 'normal',
        month: 1,
    );

    expect($pcb->amount)->toBe(387.50);
});

it('gives no spouse relief when spouse is working (KA3)', function () {
    $pcb = (new PcbCalculator)->calculateRaw(
        monthlyGross: 8000,
        employeeEpf: 880,
        taxYear: 2026,
        workerCategory: 'pemastautin',
        maritalStatus: 'married',
        spouseWorking: true,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'normal',
        month: 1,
    );

    expect($pcb->amount)->toBe(514.20);
});

it('applies flat 30% for non-resident', function () {
    $pcb = (new PcbCalculator)->calculateRaw(
        monthlyGross: 8000,
        employeeEpf: 0,
        taxYear: 2026,
        workerCategory: 'bukan_pemastautin',
        maritalStatus: 'single',
        spouseWorking: null,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'normal',
        month: 1,
    );

    expect($pcb->amount)->toBe(2400.00);
});

it('applies flat 15% for REP', function () {
    $pcb = (new PcbCalculator)->calculateRaw(
        monthlyGross: 8000,
        employeeEpf: 0,
        taxYear: 2026,
        workerCategory: 'rep',
        maritalStatus: 'single',
        spouseWorking: null,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'normal',
        month: 1,
    );

    expect($pcb->amount)->toBe(1200.00);
});

it('carries forward YTD PCB into the monthly deduction (month 2)', function () {
    $pcb = (new PcbCalculator)->calculateRaw(
        monthlyGross: 8000,
        employeeEpf: 880,
        taxYear: 2026,
        workerCategory: 'pemastautin',
        maritalStatus: 'single',
        spouseWorking: null,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'normal',
        month: 2,
        ytdGross: 8000,
        ytdPcb: 514.20,
        ytdEpf: 880,
    );

    expect($pcb->amount)->toBe(514.20);
});

it('reduces PCB by monthly zakat (annualised)', function () {
    $pcb = (new PcbCalculator)->calculateRaw(
        monthlyGross: 8000,
        employeeEpf: 880,
        taxYear: 2026,
        workerCategory: 'pemastautin',
        maritalStatus: 'single',
        spouseWorking: null,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'normal',
        month: 1,
        zakat: 100,
    );

    expect($pcb->amount)->toBe(414.20);
});

it('applies extra relief for a disabled individual (OKU self)', function () {
    $pcb = (new PcbCalculator)->calculateRaw(
        monthlyGross: 8000,
        employeeEpf: 880,
        taxYear: 2026,
        workerCategory: 'pemastautin',
        maritalStatus: 'single',
        spouseWorking: null,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'disabled',
        month: 1,
    );

    expect($pcb->amount)->toBe(419.20);
});

it('applies the RM400 individual rebate for chargeable income within threshold', function () {
    $pcb = (new PcbCalculator)->calculateRaw(
        monthlyGross: 3500,
        employeeEpf: 0,
        taxYear: 2026,
        workerCategory: 'pemastautin',
        maritalStatus: 'single',
        spouseWorking: null,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'normal',
        month: 1,
    );

    // P = 42,000 − 9,000 = 33,000 → tax 540 − rebate 400 = 140 → /12 = 11.67 → ceil 11.70
    expect($pcb->amount)->toBe(11.70);
});

it('spikes the incremental bonus tax in the declaring period and keeps baseline otherwise', function () {
    $calc = new PcbCalculator;

    // Baseline (no bonus): 8000/mo → annual 96000, EPF relief 4000, single 9000
    $base = $calc->calculateRaw(
        monthlyGross: 8000,
        employeeEpf: 880,
        taxYear: 2026,
        workerCategory: 'pemastautin',
        maritalStatus: 'single',
        spouseWorking: null,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'normal',
        month: 4,
    );
    expect($base->amount)->toBe(514.20);

    // Bonus 10,000 declared this period (prior 0 → cumulative 10000)
    $bonus = $calc->calculateRaw(
        monthlyGross: 8000,
        employeeEpf: 880,
        taxYear: 2026,
        workerCategory: 'pemastautin',
        maritalStatus: 'single',
        spouseWorking: null,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'normal',
        month: 4,
        additionalRemuneration: 10000,
        additionalRemunerationPrior: 0,
    );
    // Spike = incremental annual tax from the bonus, on top of baseline.
    expect($bonus->amount)->toBe(2414.20);
    expect((float) $bonus->breakdown['bonus_tax'])->toBe(1900.00);
    // Annual total incl bonus: tax(96,000 + 10,000 − 4,000 − 9,000)
    expect($bonus->annualTax)->toBe(8070.00);

    // A later period with no NEW bonus keeps the baseline (no spike, not zero).
    $later = $calc->calculateRaw(
        monthlyGross: 8000,
        employeeEpf: 880,
        taxYear: 2026,
        workerCategory: 'pemastautin',
        maritalStatus: 'single',
        spouseWorking: null,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'normal',
        month: 6,
        additionalRemuneration: 10000,
        additionalRemunerationPrior: 10000,
    );
    expect((float) $later->breakdown['bonus_tax'])->toBe(0.0);
    expect($later->amount)->toBe($base->amount);
});

it('applies the flat rate to additional remuneration for non-residents', function () {
    $calc = new PcbCalculator;

    $bonus = $calc->calculateRaw(
        monthlyGross: 8000,
        employeeEpf: 0,
        taxYear: 2026,
        workerCategory: 'bukan_pemastautin',
        maritalStatus: 'single',
        spouseWorking: null,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'normal',
        month: 4,
        additionalRemuneration: 1000,
        additionalRemunerationPrior: 0,
    );

    // Flat 30% on the additional remuneration, spiked on top of the 30% baseline.
    expect($bonus->amount)->toBe(2700.00);
    expect((float) $bonus->breakdown['bonus_tax'])->toBe(300.00);
});

it('does not charge PCB when the annual tax is below RM10 (myTax rule)', function () {
    $pcb = (new PcbCalculator)->calculateRaw(
        monthlyGross: 2000,
        employeeEpf: 0,
        taxYear: 2026,
        workerCategory: 'pemastautin',
        maritalStatus: 'single',
        spouseWorking: null,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'normal',
        month: 1,
    );

    // annual tax 100 − 400 rebate = 0 → below RM10 → no deduction
    expect($pcb->amount)->toBe(0.0);
});

it('deducts a sub-RM10 monthly PCB when the annual tax exceeds RM10 (myTax-verified 9.95)', function () {
    $pcb = (new PcbCalculator)->calculateRaw(
        monthlyGross: 3941.50,
        employeeEpf: round(3941.50 * 0.11, 2),
        taxYear: 2026,
        workerCategory: 'pemastautin',
        maritalStatus: 'married',
        spouseWorking: true,
        spouseDisabled: false,
        childrenTax: ['a' => ['total' => 1, 'eligible_50' => 0]],
        abilityStatus: 'normal',
        month: 1,
    );

    // annual tax 118.94 (> 10) → monthly 9.95 is charged; no per-month floor
    expect($pcb->amount)->toBe(9.95);
});

it('throws when no tax schedule exists for the year', function () {
    (new PcbCalculator)->calculateRaw(
        monthlyGross: 8000,
        employeeEpf: 880,
        taxYear: 1999,
        workerCategory: 'pemastautin',
        maritalStatus: 'single',
        spouseWorking: null,
        spouseDisabled: false,
        childrenTax: null,
        abilityStatus: 'normal',
        month: 1,
    );
})->throws(RuntimeException::class);
