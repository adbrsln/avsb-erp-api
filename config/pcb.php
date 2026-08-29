<?php

/*
|--------------------------------------------------------------------------
| LHDN PCB Tax Schedule (year-versioned)
|--------------------------------------------------------------------------
|
| Single source of truth for PCB brackets + reliefs. Adding or changing a
| tax year is data-only:
|
|   1. Add/edit the year entry below (Budget changes → new entry).
|   2. Run:  php artisan pcb:sync-schedule --year=2027
|
| The calculator picks the schedule from payroll_period.year at run time and
| fails loudly if the year is not configured/seedable here.
|
| Resident brackets: [min_chargeable, max_chargeable, rate%] (max null = top).
| flat_rates: worker_category => rate% (non-resident 30%, REP/IRDA/C-suite 15%).
| reliefs: code => [label, annual_cap]. Includes the RM400 rebate rows.
*/

$schedule2026 = [
    'resident_brackets' => [
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
    ],
    'flat_rates' => [
        'bukan_pemastautin' => 30,
        'rep' => 15,
        'irda' => 15,
        'c_suite' => 15,
    ],
    'reliefs' => [
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
    ],
];

return [
    'years' => [
        // Placeholder years sharing the YA2024+ schedule; update when LHDN
        // announces bracket/relief changes for each year.
        2025 => $schedule2026,
        2026 => $schedule2026,
        2027 => $schedule2026,
    ],
];
