<?php

namespace App\Services\Payroll;

class PcbResult
{
    public function __construct(
        public float $amount,
        public int $taxYear,
        public string $workerCategory,
        public float $chargeableIncome,
        public float $annualTax,
        public float $ytdPcb,
        public float $zakat,
        public array $breakdown = [],
    ) {}
}
