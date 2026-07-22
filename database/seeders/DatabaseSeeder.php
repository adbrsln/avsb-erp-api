<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Initial seeders only (company settings, types, staff, etc.)
        (new AvsbSeeder)->run(dummy: false, bulk: false);
    }
}
