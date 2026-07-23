<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class BulkVendorSeeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Vendor::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        Vendor::factory()
            ->count(150)
            ->sequence(fn ($seq) => ['vendor_code' => 'V-'.str_pad($seq->index + 1, 4, '0', STR_PAD_LEFT)])
            ->create();
    }
}
