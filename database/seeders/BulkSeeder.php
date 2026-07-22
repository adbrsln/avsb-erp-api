<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BulkSeeder extends Seeder
{
    public function run(): void
    {
        // Initial seeders (minus skipped) + bulk data (~150 records each)
        (new AvsbSeeder)->run(dummy: false, bulk: true);
    }
}
