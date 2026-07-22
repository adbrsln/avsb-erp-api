<?php

namespace App\Services\Payroll;

use App\Models\EPFScheduleRule;
use App\Models\StaffProfile;

class ScheduleDeterminer
{
    private array $rules;

    public function __construct()
    {
        $this->rules = EPFScheduleRule::all()->toArray();
    }

    public function determine(StaffProfile $employee): string
    {
        $age = $employee->date_of_birth ? (int) $employee->date_of_birth->diffInYears() : 0;
        $citizenship = $employee->citizenship ?? 'citizen';
        $isPr = (bool) $employee->has_pr;
        $elected = (bool) $employee->epf_member_before_aug_1998;

        foreach ($this->rules as $rule) {
            if (! $this->matches($rule, $age, $citizenship, $isPr, $elected)) {
                continue;
            }

            return $rule['schedule_code'];
        }

        return 'FLAT';
    }

    public function determineRaw(int $age, string $citizenship, bool $isPr, bool $elected): string
    {
        foreach ($this->rules as $rule) {
            if (! $this->matches($rule, $age, $citizenship, $isPr, $elected)) {
                continue;
            }

            return $rule['schedule_code'];
        }

        return 'FLAT';
    }

    private function matches(array $rule, int $age, string $citizenship, bool $isPr, bool $elected): bool
    {
        if ($rule['min_age'] !== null && $age < (int) $rule['min_age']) {
            return false;
        }
        if ($rule['max_age'] !== null && $age > (int) $rule['max_age']) {
            return false;
        }

        if ($rule['citizenship'] !== 'any' && $rule['citizenship'] !== null) {
            if ($citizenship !== $rule['citizenship']) {
                return false;
            }
        }

        if ($rule['is_pr'] !== 'any' && $rule['is_pr'] !== null) {
            $expectedPr = $rule['is_pr'] === 'yes';
            if ($isPr !== $expectedPr) {
                return false;
            }
        }

        if ($rule['elected_before_1998'] !== 'any' && $rule['elected_before_1998'] !== null) {
            $expectedElected = $rule['elected_before_1998'] === 'yes';
            if ($elected !== $expectedElected) {
                return false;
            }
        }

        return true;
    }
}
