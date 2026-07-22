<?php

namespace App\Services;

use App\Models\FiscalPeriod;

class PeriodLockService
{
    public static function assertOpen(string $entryDate): ?string
    {
        $locked = FiscalPeriod::whereIn('status', ['closed', 'locked'])
            ->where('start_date', '<=', $entryDate)
            ->where('end_date', '>=', $entryDate)
            ->exists();

        if ($locked) {
            return 'The entry date falls within a closed or locked period. Manual journal entries cannot be posted in closed periods.';
        }
        return null;
    }
}
