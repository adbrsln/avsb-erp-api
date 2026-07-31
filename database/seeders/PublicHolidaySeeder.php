<?php

namespace Database\Seeders;

use App\Models\PublicHoliday;

class PublicHolidaySeeder
{
    /**
     * Recurring Malaysian public holidays (fixed-date) + sample 2026 fixed dates.
     */
    public function run(): void
    {
        $recurring = [
            ['name' => 'New Year', 'date' => '2000-01-01'],
            ['name' => 'Labour Day', 'date' => '2000-05-01'],
            ['name' => 'National Day', 'date' => '2000-08-31'],
            ['name' => 'Malaysia Day', 'date' => '2000-09-16'],
            ['name' => 'Christmas Day', 'date' => '2000-12-25'],
        ];

        $fixed2026 = [
            ['name' => 'Chinese New Year', 'date' => '2026-02-17'],
            ['name' => 'Hari Raya Puasa', 'date' => '2026-03-20'],
            ['name' => 'Hari Raya Haji', 'date' => '2026-05-27'],
            ['name' => 'Deepavali', 'date' => '2026-11-08'],
        ];

        foreach ($recurring as $h) {
            PublicHoliday::firstOrCreate(
                ['date' => $h['date'], 'is_recurring' => true],
                ['name' => $h['name'], 'year' => null]
            );
        }

        foreach ($fixed2026 as $h) {
            PublicHoliday::firstOrCreate(
                ['date' => $h['date'], 'is_recurring' => false],
                ['name' => $h['name'], 'year' => 2026]
            );
        }
    }
}
