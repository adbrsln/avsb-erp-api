<?php

namespace App\Services\Payroll;

readonly class EisResult
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
            'eis_employer' => $this->employerAmount,
            'eis_employee' => $this->employeeAmount,
            'eis_total' => $this->total(),
            'is_tiered' => $this->isTiered,
            'is_capped' => $this->isCapped,
        ];
    }
}
