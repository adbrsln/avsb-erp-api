<?php

namespace App\Services\Payroll;

use App\Models\EPFContributionTier;
use App\Models\EPFSchedule;
use App\Models\StaffProfile;

class EPFCalculator
{
    public function calculate(StaffProfile $employee): EPFResult
    {
        $scheduleCode = (new ScheduleDeterminer)->determine($employee);
        $schedule = EPFSchedule::find($scheduleCode);

        if (!$schedule) {
            throw new \RuntimeException("EPF schedule '{$scheduleCode}' not found.");
        }

        $salary = (float) ($employee->basic_salary ?? 0);

        if ($scheduleCode === 'FLAT') {
            return new EPFResult(
                scheduleCode: $scheduleCode,
                salary: $salary,
                employerAmount: round($salary * $schedule->employer_rate / 100, 2),
                employeeAmount: round($salary * $schedule->employee_rate / 100, 2),
                isTiered: false,
            );
        }

        if ($salary <= $schedule->max_tier_wage) {
            $tier = EPFContributionTier::where('schedule_code', $scheduleCode)
                ->where('wage_from', '<=', $salary)
                ->where('wage_to', '>=', $salary)
                ->first();

            if ($tier) {
                return new EPFResult(
                    scheduleCode: $scheduleCode,
                    salary: $salary,
                    employerAmount: (float) $tier->employer_amount,
                    employeeAmount: (float) $tier->employee_amount,
                    isTiered: true,
                    tier: $tier->toArray(),
                );
            }
        }

        return new EPFResult(
            scheduleCode: $scheduleCode,
            salary: $salary,
            employerAmount: round($salary * $schedule->employer_rate / 100, 2),
            employeeAmount: round($salary * $schedule->employee_rate / 100, 2),
            isTiered: false,
        );
    }

    public function calculateRaw(
        float $salary,
        string $citizenship,
        bool $isPr,
        bool $electedBefore1998,
        string $dateOfBirth,
    ): EPFResult {
        $age = (new \DateTimeImmutable($dateOfBirth))->diff(new \DateTimeImmutable)->y;
        $scheduleCode = (new ScheduleDeterminer)->determineRaw($age, $citizenship, $isPr, $electedBefore1998);
        $schedule = EPFSchedule::find($scheduleCode);

        if (!$schedule) {
            throw new \RuntimeException("EPF schedule '{$scheduleCode}' not found.");
        }

        if ($scheduleCode === 'FLAT') {
            return new EPFResult(
                scheduleCode: $scheduleCode,
                salary: $salary,
                employerAmount: round($salary * $schedule->employer_rate / 100, 2),
                employeeAmount: round($salary * $schedule->employee_rate / 100, 2),
                isTiered: false,
            );
        }

        if ($salary <= $schedule->max_tier_wage) {
            $tier = EPFContributionTier::where('schedule_code', $scheduleCode)
                ->where('wage_from', '<=', $salary)
                ->where('wage_to', '>=', $salary)
                ->first();

            if ($tier) {
                return new EPFResult(
                    scheduleCode: $scheduleCode,
                    salary: $salary,
                    employerAmount: (float) $tier->employer_amount,
                    employeeAmount: (float) $tier->employee_amount,
                    isTiered: true,
                    tier: $tier->toArray(),
                );
            }
        }

        return new EPFResult(
            scheduleCode: $scheduleCode,
            salary: $salary,
            employerAmount: round($salary * $schedule->employer_rate / 100, 2),
            employeeAmount: round($salary * $schedule->employee_rate / 100, 2),
            isTiered: false,
        );
    }
}
