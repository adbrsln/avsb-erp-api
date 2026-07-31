<?php

namespace App\Services;

use App\Models\PublicHoliday;
use Carbon\Carbon;

class HolidayService
{
    /**
     * Returns a map of holiday dates (Y-m-d) to holiday names within the range.
     * Recurring holidays are expanded across any year boundary.
     */
    public static function holidaysBetween(Carbon $start, Carbon $end): array
    {
        $holidays = PublicHoliday::all();

        $result = [];
        $current = $start->copy()->startOfDay();

        while ($current->lte($end)) {
            foreach ($holidays as $holiday) {
                if ($holiday->isOn($current)) {
                    $result[$current->format('Y-m-d')] = $holiday->name;
                    break;
                }
            }
            $current->addDay();
        }

        return $result;
    }

    /**
     * Returns the holiday name for the given date, or null if not a holiday.
     */
    public static function isHoliday(Carbon $date): ?string
    {
        $holidays = PublicHoliday::all();

        foreach ($holidays as $holiday) {
            if ($holiday->isOn($date)) {
                return $holiday->name;
            }
        }

        return null;
    }
}
