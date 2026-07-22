<?php

namespace App\Services\Payroll;

use App\Models\CompanySetting;
use App\Models\Socso24hTier;

class Socso24Calculator
{
    public function calculate(float $salary, string $category = 'first'): array
    {
        $phase = (int) (CompanySetting::first()?->socso_24h_phase ?? 1);

        $tier = Socso24hTier::where('category', $category)
            ->where('phase', $phase)
            ->where('wage_from', '<=', $salary)
            ->where(function ($q) use ($salary) {
                $q->where('wage_to', '>=', $salary)->orWhereNull('wage_to');
            })
            ->first();

        $amount = $tier ? (float) $tier->employee_amount : 0;

        return [
            'amount' => $amount,
            'phase' => $phase,
            'category' => $category,
        ];
    }
}
