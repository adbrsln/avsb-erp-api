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
            $amount = $this->roundSen5($monthlyGross * $rate / 100);

            return new PcbResult(
                amount: $amount,
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

        // EPF relief: capped at RM4,000/year, applied cumulatively.
        $epfCap = (float) ($reliefs['epf'] ?? 4000.0);
        $epfRelief = min($ytdEpf + $employeeEpf, $epfCap);
        $effectiveEpf = max(0.0, $epfRelief - $ytdEpf);

        $monthlyChargeable = max(0.0, $monthlyGross - $effectiveEpf);

        $reliefTotal = $this->annualReliefs(
            $reliefs,
            $maritalStatus,
            $spouseWorking,
            $spouseDisabled,
            $childrenTax,
            $abilityStatus
        );

        $chargeableIncome = max(0.0, ($monthlyChargeable * 12) + $additionalRemuneration - $reliefTotal);

        $annualTax = $this->taxOn($this->schedule->brackets($taxYear, 'pemastautin'), $chargeableIncome);

        $annualZakat = $zakat * 12;
        $annualPcb = max(0.0, $annualTax - $annualZakat);

        $month = max(1, min(12, $month));
        $remainingMonths = 13 - $month;
        $amount = max(0.0, ($annualPcb - $ytdPcb) / $remainingMonths);
        $amount = $this->roundSen5($amount);

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
                'epf_relief_applied' => $effectiveEpf,
                'monthly_chargeable' => $monthlyChargeable,
                'annual_chargeable' => $chargeableIncome,
                'reliefs_total' => $reliefTotal,
                'annual_zakat' => $annualZakat,
                'remaining_months' => $remainingMonths,
            ],
        );
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

    private function roundSen5(float $amount): float
    {
        return round($amount * 20) / 20;
    }
}
