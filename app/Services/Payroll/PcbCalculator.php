<?php

namespace App\Services\Payroll;

use App\Models\StaffProfile;

class PcbCalculator
{
    private const CHILD_RATES = [
        'a' => 2000.0,   // under 18
        'b' => 2000.0,   // 18+ studying in Malaysia
        'c' => 8000.0,   // 18+ full-time diploma+ (MY) / degree+ (abroad)
        'd' => 6000.0,   // disabled
        'e' => 14000.0,  // disabled + studying
    ];

    private PcbTaxSchedule $schedule;

    public function __construct(?PcbTaxSchedule $schedule = null)
    {
        $this->schedule = $schedule ?? new PcbTaxSchedule;
    }

    public function calculate(StaffProfile $employee, int $taxYear, PcbContext $ctx): PcbResult
    {
        return $this->calculateRaw(
            monthlyGross: $ctx->monthlyGross,
            employeeEpf: $ctx->employeeEpf,
            taxYear: $taxYear,
            workerCategory: $employee->worker_category ?? 'pemastautin',
            maritalStatus: $employee->marital_status,
            spouseWorking: $employee->spouse_working,
            spouseDisabled: (bool) $employee->spouse_disabled,
            childrenTax: $employee->children_tax,
            abilityStatus: $employee->ability_status ?? 'normal',
            zakat: $ctx->zakat,
            ytdGross: $ctx->ytdGross,
            ytdPcb: $ctx->ytdPcb,
            ytdEpf: $ctx->ytdEpf,
            additionalRemuneration: $ctx->additionalRemuneration,
            additionalRemunerationPrior: $ctx->additionalRemunerationPrior,
            month: $ctx->month,
        );
    }

    public function calculateRaw(
        float $monthlyGross,
        float $employeeEpf,
        int $taxYear,
        string $workerCategory,
        ?string $maritalStatus,
        ?bool $spouseWorking,
        bool $spouseDisabled,
        ?array $childrenTax,
        string $abilityStatus,
        float $zakat = 0,
        float $ytdGross = 0,
        float $ytdPcb = 0,
        float $ytdEpf = 0,
        float $additionalRemuneration = 0,
        float $additionalRemunerationPrior = 0,
        int $month = 1,
    ): PcbResult {
        $this->schedule->assertScheduleExists($taxYear);

        // Flat-rate categories (non-resident 30%, REP/IRDA/C-suite 15%).
        if ($workerCategory !== 'pemastautin') {
            $rate = $this->flatRate($taxYear, $workerCategory);
            $monthlyAmount = $this->ceilSen5($monthlyGross * $rate / 100);
            // RM10 rule applied to the ANNUAL tax (myTax portal does not floor
            // the monthly amount) — no deduction only if the whole year's tax
            // is under RM10.
            $amount = ($monthlyGross * $rate / 100 * 12) < 10.0 ? 0.0 : $monthlyAmount;

            // Design A: the period declaring additional remuneration spikes
            // the flat-rate tax on that additional amount.
            $bonusTax = $additionalRemuneration > $additionalRemunerationPrior
                ? $this->ceilSen5(($additionalRemuneration - $additionalRemunerationPrior) * $rate / 100)
                : 0.0;
            $amount = $this->ceilSen5($amount + $bonusTax);

            return new PcbResult(
                amount: $amount,
                taxYear: $taxYear,
                workerCategory: $workerCategory,
                chargeableIncome: 0.0,
                annualTax: 0.0,
                ytdPcb: 0.0,
                zakat: 0.0,
                breakdown: [
                    'worker_category' => $workerCategory,
                    'flat_rate' => $rate,
                    'bonus_tax' => $bonusTax,
                ],
            );
        }

        $reliefs = $this->schedule->reliefs($taxYear);

        $month = max(1, min(12, $month));
        $remainingMonths = 13 - $month;

        // EPF relief is the ANNUAL cap (RM4,000), independent of how many
        // months remain — otherwise the annual tax target drifts by month.
        $epfCap = (float) ($reliefs['epf'] ?? 4000.0);
        $annualEpf = min($employeeEpf * 12, $epfCap);

        $reliefTotal = $this->annualReliefs(
            $reliefs,
            $maritalStatus,
            $spouseWorking,
            $spouseDisabled,
            $childrenTax,
            $abilityStatus
        );

        // Design A (bonus spike + spread): every month pays a constant
        // baseline of the no-bonus annual tax ÷ 12. The period that declares
        // additional remuneration spikes the incremental tax that the bonus
        // adds to the annual total. Across the year the total is exactly the
        // annual tax on (monthly gross × 12 + total additional remuneration).
        $annualGross = ($monthlyGross * 12);
        $chargeableBase = max(0.0, $annualGross - $annualEpf - $reliefTotal);
        $annualTaxBase = $this->annualTaxAfterReliefs($taxYear, $chargeableBase, $reliefs, $maritalStatus, $spouseWorking);

        $chargeableWithBonus = max(0.0, $annualGross + $additionalRemuneration - $annualEpf - $reliefTotal);
        $annualTaxWithBonus = $this->annualTaxAfterReliefs($taxYear, $chargeableWithBonus, $reliefs, $maritalStatus, $spouseWorking);

        $annualZakat = $zakat * 12;

        $baselineMonthly = max(0.0, ($annualTaxBase - $annualZakat) / 12);

        // Incremental tax from the additional remuneration declared in THIS
        // period (delta between the new cumulative and the prior cumulative).
        $chargeableWithPrior = max(0.0, $annualGross + $additionalRemunerationPrior - $annualEpf - $reliefTotal);
        $annualTaxWithPrior = $this->annualTaxAfterReliefs($taxYear, $chargeableWithPrior, $reliefs, $maritalStatus, $spouseWorking);
        $bonusTax = max(0.0, $annualTaxWithBonus - $annualTaxWithPrior);

        // RM10 rule on the annual tax: no deduction when the whole year's tax
        // is under RM10 (myTax does not floor the monthly amount).
        $annualPcb = max(0.0, $annualTaxBase - $annualZakat);
        if ($annualPcb < 10.0 && $bonusTax == 0.0) {
            $amount = 0.0;
        } else {
            // Baseline every month, plus the incremental bonus tax spiked in
            // the declaring period (Design A).
            $amount = $this->ceilSen5($baselineMonthly + $bonusTax);
        }

        return new PcbResult(
            amount: $amount,
            taxYear: $taxYear,
            workerCategory: $workerCategory,
            chargeableIncome: $chargeableWithBonus,
            annualTax: $annualTaxWithBonus,
            ytdPcb: $ytdPcb,
            zakat: $zakat,
            breakdown: [
                'worker_category' => $workerCategory,
                'monthly_gross' => $monthlyGross,
                'epf_employee' => $employeeEpf,
                'annual_epf_relief' => $annualEpf,
                'annual_gross' => $annualGross + $additionalRemuneration,
                'annual_chargeable' => $chargeableWithBonus,
                'reliefs_total' => $reliefTotal,
                'annual_zakat' => $annualZakat,
                'remaining_months' => $remainingMonths,
                'additional_remuneration' => $additionalRemuneration,
                'additional_remuneration_prior' => $additionalRemunerationPrior,
                'bonus_tax' => $bonusTax,
                'baseline_monthly' => $baselineMonthly,
            ],
        );
    }

    private function annualTaxAfterReliefs(int $taxYear, float $chargeable, array $reliefs, ?string $maritalStatus, ?bool $spouseWorking): float
    {
        $tax = $this->taxOn($this->schedule->brackets($taxYear, 'pemastautin'), $chargeable);
        $tax = $this->applyRebate($tax, $chargeable, $reliefs, $maritalStatus, $spouseWorking);

        return $tax;
    }

    private function applyRebate(
        float $annualTax,
        float $chargeableIncome,
        array $reliefs,
        ?string $maritalStatus,
        ?bool $spouseWorking,
    ): float {
        $rebate = 0.0;
        $threshold = (float) ($reliefs['rebate_threshold'] ?? 35000.0);

        if ($chargeableIncome <= $threshold) {
            $rebate += (float) ($reliefs['rebate_individual'] ?? 400.0);
            if ($maritalStatus === 'married' && $spouseWorking === false) {
                $rebate += (float) ($reliefs['rebate_spouse'] ?? 400.0);
            }
        }

        return max(0.0, $annualTax - $rebate);
    }

    private function flatRate(int $taxYear, string $workerCategory): float
    {
        $brackets = $this->schedule->brackets($taxYear, $workerCategory);
        if (empty($brackets)) {
            throw new \RuntimeException("No PCB rate found for category '{$workerCategory}' in year {$taxYear}.");
        }

        return $brackets[0]['rate'];
    }

    private function annualReliefs(
        array $reliefs,
        ?string $maritalStatus,
        ?bool $spouseWorking,
        bool $spouseDisabled,
        ?array $childrenTax,
        string $abilityStatus,
    ): float {
        $k = (float) ($reliefs['individual'] ?? 9000.0);

        if ($maritalStatus === 'married' && $spouseWorking === false) {
            $k += (float) ($reliefs['spouse'] ?? 4000.0);
        }

        if ($maritalStatus === 'married' && $spouseDisabled) {
            $k += (float) ($reliefs['disabled_spouse'] ?? 6000.0);
        }

        if ($abilityStatus === 'disabled') {
            $k += (float) ($reliefs['disabled_self'] ?? 6000.0);
        }

        foreach ($childrenTax ?? [] as $category => $data) {
            if (! isset(self::CHILD_RATES[$category])) {
                continue;
            }
            $total = (int) ($data['total'] ?? 0);
            $eligible50 = (int) ($data['eligible_50'] ?? 0);
            $full = max(0, $total - $eligible50);
            $rate = self::CHILD_RATES[$category];
            $k += $full * $rate;
            $k += $eligible50 * $rate * 0.5;
        }

        return $k;
    }

    /**
     * @param  array<int, array{min_chargeable: float, max_chargeable: ?float, rate: float}>  $brackets
     */
    private function taxOn(array $brackets, float $income): float
    {
        $tax = 0.0;
        foreach ($brackets as $bracket) {
            $min = $bracket['min_chargeable'];
            $max = $bracket['max_chargeable'];
            if ($income <= $min) {
                break;
            }
            $bandTop = $max !== null ? min($income, $max) : $income;
            $tax += max(0.0, $bandTop - $min) * $bracket['rate'] / 100;
        }

        // Round to cents: float accumulation can land a boundary value a
        // fraction above the true 5-sen point and over-charge (e.g. 5470/mo
        // → 1790.4000...0001 tax → 149.25 instead of LHDN's 149.20).
        return round($tax, 2);
    }

    private function ceilSen5(float $amount): float
    {
        // Round to cents first: float division (e.g. 1790.40/12) can land a
        // boundary value a fraction above the true 5-sen point (149.25 vs
        // LHDN's 149.20).
        return ceil(round($amount, 2) * 20) / 20;
    }
}
