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

it('calculates PCB for a single resident (RM8,000, EPF 11%)', function () {
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

    expect($pcb->amount)->toBe(410.30);
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

    expect($pcb->amount)->toBe(294.05);
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

    expect($pcb->amount)->toBe(410.30);
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
        ytdPcb: 410.30,
        ytdEpf: 880,
    );

    expect($pcb->amount)->toBe(410.30);
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

    expect($pcb->amount)->toBe(310.30);
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

    expect($pcb->amount)->toBe(315.30);
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
