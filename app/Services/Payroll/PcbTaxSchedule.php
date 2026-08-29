<?php

namespace App\Services\Payroll;

use App\Models\PcbRelief;
use App\Models\PcbTaxBracket;

class PcbTaxSchedule
{
    /**
     * @return array<int, array{min_chargeable: float, max_chargeable: ?float, rate: float}>
     */
    public function brackets(int $taxYear, string $workerCategory = 'pemastautin'): array
    {
        return PcbTaxBracket::where('year', $taxYear)
            ->where('worker_category', $workerCategory)
            ->orderBy('min_chargeable')
            ->get()
            ->map(fn ($b) => [
                'min_chargeable' => (float) $b->min_chargeable,
                'max_chargeable' => $b->max_chargeable !== null ? (float) $b->max_chargeable : null,
                'rate' => (float) $b->rate,
            ])
            ->values()
            ->toArray();
    }

    /**
     * @return array<string, float> relief code => annual cap
     */
    public function reliefs(int $taxYear): array
    {
        return PcbRelief::where('year', $taxYear)
            ->get()
            ->pluck('annual_cap', 'code')
            ->map(fn ($cap) => (float) $cap)
            ->toArray();
    }

    public function assertScheduleExists(int $taxYear): void
    {
        if (! PcbTaxBracket::where('year', $taxYear)->exists()) {
            throw new \RuntimeException("No PCB tax schedule found for year {$taxYear}.");
        }
    }
}
