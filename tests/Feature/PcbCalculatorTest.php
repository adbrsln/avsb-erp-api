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

it('computes one-shot PCB for additional remuneration (bonus)', function () {
    $base = (new PcbCalculator)->calculateRaw(
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

    // CS = tax(83,000 + 10,000) = tax(93,000) = 8,070; PCB(B) = 0 + 514.20
    $additional = (new PcbCalculator)->additionalRemunerationPcb($base, 10000, 1100);

    expect($additional)->toBe(7555.80);
});

it('applies the flat rate to additional remuneration for non-residents', function () {
    $base = (new PcbCalculator)->calculateRaw(
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

    expect((new PcbCalculator)->additionalRemunerationPcb($base, 1000, 0))->toBe(300.00);
});

it('does not charge PCB when the monthly amount is below RM10 (LHDN rule)', function () {
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

    expect($pcb->amount)->toBe(0.0);
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
