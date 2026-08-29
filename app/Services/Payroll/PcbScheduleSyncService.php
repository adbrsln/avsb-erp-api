<?php

namespace App\Services\Payroll;

use App\Models\PcbRelief;
use App\Models\PcbTaxBracket;

class PcbScheduleSyncService
{
    /**
     * Upsert the configured schedule for a tax year into the DB.
     * updateOrCreate (not firstOrCreate) so re-runs apply config changes.
     *
     * @return array{year: int, brackets: int, reliefs: int}
     */
    public function sync(int $year, bool $dryRun = false): array
    {
        $schedule = config("pcb.years.{$year}");
        if (! is_array($schedule)) {
            throw new \RuntimeException("No PCB schedule configured for year {$year}. Add it to config/pcb.php first.");
        }

        $brackets = 0;
        foreach ($schedule['resident_brackets'] as [$min, $max, $rate]) {
            $brackets++;
            if ($dryRun) {
                continue;
            }
            PcbTaxBracket::updateOrCreate(
                ['year' => $year, 'worker_category' => 'pemastautin', 'min_chargeable' => $min],
                ['max_chargeable' => $max, 'rate' => $rate]
            );
        }

        foreach ($schedule['flat_rates'] as $category => $rate) {
            $brackets++;
            if ($dryRun) {
                continue;
            }
            PcbTaxBracket::updateOrCreate(
                ['year' => $year, 'worker_category' => $category, 'min_chargeable' => 0],
                ['max_chargeable' => null, 'rate' => $rate]
            );
        }

        $reliefs = 0;
        foreach ($schedule['reliefs'] as $code => [$label, $cap]) {
            $reliefs++;
            if ($dryRun) {
                continue;
            }
            PcbRelief::updateOrCreate(
                ['year' => $year, 'code' => $code],
                ['label' => $label, 'annual_cap' => $cap]
            );
        }

        return ['year' => $year, 'brackets' => $brackets, 'reliefs' => $reliefs];
    }
}
