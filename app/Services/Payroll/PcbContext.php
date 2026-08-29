<?php

namespace App\Services\Payroll;

class PcbContext
{
    public function __construct(
        public float $monthlyGross,
        public float $employeeEpf,
        public float $ytdGross = 0,
        public float $ytdPcb = 0,
        public float $ytdEpf = 0,
        public float $zakat = 0,
        public float $additionalRemuneration = 0,
        public int $month = 1,
    ) {}
}
