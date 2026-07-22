<?php

namespace App\Services\Payroll;

readonly class SocsoResult
{
    public function __construct(
        public float $salary,
        public float $employerAmount,
        public float $employeeAmount,
        public bool $isTiered = false,
        public bool $isCapped = false,
    ) {}

    public function total(): float
    {
        return $this->employerAmount + $this->employeeAmount;
    }

    public function toArray(): array
    {
        return [
            'salary' => $this->salary,
            'socso_employer' => $this->employerAmount,
            'socso_employee' => $this->employeeAmount,
            'socso_total' => $this->total(),
            'is_tiered' => $this->isTiered,
            'is_capped' => $this->isCapped,
        ];
    }
}
