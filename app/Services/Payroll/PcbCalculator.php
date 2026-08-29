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
        int $month = 1,
    ): PcbResult {
        $this->schedule->assertScheduleExists($taxYear);

        // Flat-rate categories (non-resident 30%, REP/IRDA/C-suite 15%).
        if ($workerCategory !== 'pemastautin') {
            $rate = $this->flatRate($taxYear, $workerCategory);
            $amount = $this->ceilSen5($monthlyGross * $rate / 100);

            return new PcbResult(
                amount: $this->applyMinimumPcb($amount),
                taxYear: $taxYear,
                workerCategory: $workerCategory,
                chargeableIncome: 0.0,
                annualTax: 0.0,
                ytdPcb: 0.0,
                zakat: 0.0,
                breakdown: ['worker_category' => $workerCategory, 'flat_rate' => $rate],
            );
        }

        $reliefs = $this->schedule->reliefs($taxYear);

        $month = max(1, min(12, $month));
        $remainingMonths = 13 - $month;

        // Annualise gross directly; EPF relief is the ANNUAL cap (RM4,000),
        // not the monthly contribution × 12 (verified against calcpcbplus:
        // RM8,000 + EPF 880 → chargeable 83,000 = 96,000 − 4,000 − 9,000).
        $annualGross = ($monthlyGross * 12) + $additionalRemuneration;
        $epfCap = (float) ($reliefs['epf'] ?? 4000.0);
        $annualEpf = min($ytdEpf + ($employeeEpf * $remainingMonths), $epfCap);

        $reliefTotal = $this->annualReliefs(
            $reliefs,
            $maritalStatus,
            $spouseWorking,
            $spouseDisabled,
            $childrenTax,
            $abilityStatus
        );

        $chargeableIncome = max(0.0, $annualGross - $annualEpf - $reliefTotal);

        $annualTax = $this->taxOn($this->schedule->brackets($taxYear, 'pemastautin'), $chargeableIncome);
        $annualTax = $this->applyRebate($annualTax, $chargeableIncome, $reliefs, $maritalStatus, $spouseWorking);

        $annualZakat = $zakat * 12;
        $annualPcb = max(0.0, $annualTax - $annualZakat);

        $amount = max(0.0, ($annualPcb - $ytdPcb) / $remainingMonths);
        $amount = $this->applyMinimumPcb($this->ceilSen5($amount));

        return new PcbResult(
            amount: $amount,
            taxYear: $taxYear,
            workerCategory: $workerCategory,
            chargeableIncome: $chargeableIncome,
            annualTax: $annualTax,
            ytdPcb: $ytdPcb,
            zakat: $zakat,
            breakdown: [
                'worker_category' => $workerCategory,
                'monthly_gross' => $monthlyGross,
                'epf_employee' => $employeeEpf,
                'annual_epf_relief' => $annualEpf,
                'annual_gross' => $annualGross,
                'annual_chargeable' => $chargeableIncome,
                'reliefs_total' => $reliefTotal,
                'annual_zakat' => $annualZakat,
                'remaining_months' => $remainingMonths,
            ],
        );
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

    /**
     * LHDN additional-remuneration (saraan tambahan) PCB — one-shot in the
     * month the bonus is paid: PCB(C) = CS − [PCB(B) + Z], where
     * CS = annual tax on (ordinary chargeable + additional net),
     * PCB(B) = cumulative PCB incl. this month's ordinary deduction,
     * Z = accumulated zakat. Subsequent months converge to zero because
     * the year's tax was front-loaded in this month.
     */
    public function additionalRemunerationPcb(PcbResult $base, float $additionalGross, float $additionalEpf = 0): float
    {
        if ($base->workerCategory !== 'pemastautin') {
            $rate = $this->flatRate($base->taxYear, $base->workerCategory);

            return max(0.0, $this->ceilSen5($additionalGross * $rate / 100));
        }

        $reliefs = $this->schedule->reliefs($base->taxYear);
        $epfCap = (float) ($reliefs['epf'] ?? 4000.0);
        $annualEpfBase = (float) ($base->breakdown['annual_epf_relief'] ?? 0);
        $combinedEpf = min($annualEpfBase + $additionalEpf, $epfCap);
        $additionalNet = $additionalGross - max(0.0, $combinedEpf - $annualEpfBase);

        $cs = $this->taxOn(
            $this->schedule->brackets($base->taxYear, 'pemastautin'),
            $base->chargeableIncome + $additionalNet
        );
        $pcbB = $base->ytdPcb + $base->amount;
        $zAccum = (float) ($base->breakdown['annual_zakat'] ?? 0);

        return max(0.0, $this->ceilSen5($cs - $pcbB - $zAccum));
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

        return $tax;
    }

    private function ceilSen5(float $amount): float
    {
        return ceil($amount * 20) / 20;
    }

    /**
     * LHDN rule (per calcpcbplus note): no PCB charged when the monthly
     * deduction is less than RM10.
     */
    private function applyMinimumPcb(float $amount): float
    {
        return $amount < 10.0 ? 0.0 : $amount;
    }
}
