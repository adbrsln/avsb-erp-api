<?php

namespace Database\Seeders;

use App\Services\Payroll\PcbScheduleSyncService;
use Illuminate\Database\Seeder;

class PcbTaxSeeder extends Seeder
{
    public function run(?int $year = null): void
    {
        $years = $year !== null ? [$year] : array_keys(config('pcb.years', []));

        foreach ($years as $y) {
            (new PcbScheduleSyncService)->sync((int) $y);
        }
    }
}
