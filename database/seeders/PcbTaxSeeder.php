<?php

namespace Database\Seeders;

use App\Models\PcbRelief;
use App\Models\PcbTaxBracket;
use Illuminate\Database\Seeder;

class PcbTaxSeeder extends Seeder
{
    public function run(?int $year = null): void
    {
        $year = $year ?? 2026;

        $residentBrackets = [
            [0, 5000, 0],
            [5000, 20000, 1],
            [20000, 35000, 3],
            [35000, 50000, 6],
            [50000, 70000, 11],
            [70000, 100000, 19],
            [100000, 400000, 25],
            [400000, 600000, 26],
            [600000, 2000000, 28],
            [2000000, null, 30],
        ];

        foreach ($residentBrackets as [$min, $max, $rate]) {
            PcbTaxBracket::firstOrCreate(
                ['year' => $year, 'worker_category' => 'pemastautin', 'min_chargeable' => $min],
                ['max_chargeable' => $max, 'rate' => $rate]
            );
        }

        $flatRates = [
            'bukan_pemastautin' => 30,
            'rep' => 15,
            'irda' => 15,
            'c_suite' => 15,
        ];

        foreach ($flatRates as $category => $rate) {
            PcbTaxBracket::firstOrCreate(
                ['year' => $year, 'worker_category' => $category, 'min_chargeable' => 0],
                ['max_chargeable' => null, 'rate' => $rate]
            );
        }

        $reliefs = [
            'individual' => ['Individual (self)', 9000],
            'spouse' => ['Spouse (KA2 only)', 4000],
            'child_under_18' => ['Child under 18', 2000],
            'child_tertiary' => ['Child 18+ in tertiary education', 8000],
            'disabled_self' => ['Disabled individual (self)', 6000],
            'disabled_spouse' => ['Disabled spouse', 6000],
            'disabled_child' => ['Disabled child (additional)', 6000],
            'epf' => ['EPF employee contribution', 4000],
            'rebate_individual' => ['Individual tax rebate', 400],
            'rebate_spouse' => ['Spouse tax rebate (KA2)', 400],
            'rebate_threshold' => ['Rebate chargeable-income threshold', 35000],
        ];

        foreach ($reliefs as $code => [$label, $cap]) {
            PcbRelief::firstOrCreate(
                ['year' => $year, 'code' => $code],
                ['label' => $label, 'annual_cap' => $cap]
            );
        }
    }
}
