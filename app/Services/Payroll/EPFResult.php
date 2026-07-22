<?php

namespace App\Services\Payroll;

readonly class EPFResult
{
    public function __construct(
        public string $scheduleCode,
        public float $salary,
        public float $employerAmount,
        public float $employeeAmount,
        public bool $isTiered = false,
        public ?array $tier = null,
    ) {}

    public function total(): float
    {
        return $this->employerAmount + $this->employeeAmount;
    }

    public function toArray(): array
    {
        return [
            'schedule_code' => $this->scheduleCode,
            'salary' => $this->salary,
            'epf_employer' => $this->employerAmount,
            'epf_employee' => $this->employeeAmount,
            'epf_total' => $this->total(),
            'is_tiered' => $this->isTiered,
        ];
    }
}
