<?php

namespace Database\Seeders;

use App\Models\Vendor;
use App\Services\NumberingService;
use Illuminate\Support\Facades\DB;

class BulkVendorSeeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Vendor::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $numService = new NumberingService;

        Vendor::factory()
            ->count(150)
            ->sequence(function () use ($numService) {
                return ['vendor_code' => $numService->generate('vendor')];
            })
            ->create();
    }
}
