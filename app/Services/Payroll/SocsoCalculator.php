<?php

namespace App\Services\Payroll;

use App\Models\SocsoContributionTier;

class SocsoCalculator
{
    private const CEILING_WAGE = 7500.00;

    private ?SocsoContributionTier $highestTier = null;

    public function calculate(float $salary): SocsoResult
    {
        $tier = SocsoContributionTier::where('wage_from', '<=', $salary)
            ->where('wage_to', '>=', $salary)
            ->first();

        if ($tier) {
            return new SocsoResult(
                salary: $salary,
                employerAmount: (float) $tier->employer_amount,
                employeeAmount: (float) $tier->employee_amount,
                isTiered: true,
            );
        }

        if ($salary > self::CEILING_WAGE) {
            $capped = $this->getHighestTier();
            return new SocsoResult(
                salary: $salary,
                employerAmount: (float) $capped->employer_amount,
                employeeAmount: (float) $capped->employee_amount,
                isTiered: true,
                isCapped: true,
            );
        }

        return new SocsoResult(salary: $salary, employerAmount: 0, employeeAmount: 0);
    }

    public function getAllTiers(): array
    {
        return SocsoContributionTier::orderBy('wage_from')
            ->get()
            ->toArray();
    }

    private function getHighestTier(): SocsoContributionTier
    {
        if ($this->highestTier === null) {
            $this->highestTier = SocsoContributionTier::orderByDesc('wage_to')->first();
        }
        return $this->highestTier;
    }
}
