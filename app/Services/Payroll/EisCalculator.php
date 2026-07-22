<?php

namespace App\Services\Payroll;

use App\Models\EisContributionTier;

class EisCalculator
{
    private const CEILING_WAGE = 7500.00;

    private ?EisContributionTier $highestTier = null;

    public function calculate(float $salary): EisResult
    {
        $tier = EisContributionTier::where('wage_from', '<=', $salary)
            ->where('wage_to', '>=', $salary)
            ->first();

        if ($tier) {
            return new EisResult(
                salary: $salary,
                employerAmount: (float) $tier->employer_amount,
                employeeAmount: (float) $tier->employee_amount,
                isTiered: true,
            );
        }

        if ($salary > self::CEILING_WAGE) {
            $capped = $this->getHighestTier();

            return new EisResult(
                salary: $salary,
                employerAmount: (float) $capped->employer_amount,
                employeeAmount: (float) $capped->employee_amount,
                isTiered: true,
                isCapped: true,
            );
        }

        return new EisResult(salary: $salary, employerAmount: 0, employeeAmount: 0);
    }

    public function getAllTiers(): array
    {
        return EisContributionTier::orderBy('wage_from')
            ->get()
            ->toArray();
    }

    private function getHighestTier(): EisContributionTier
    {
        if ($this->highestTier === null) {
            $this->highestTier = EisContributionTier::orderByDesc('wage_to')->first();
        }

        return $this->highestTier;
    }
}
