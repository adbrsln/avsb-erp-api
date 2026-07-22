<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DummySeeder extends Seeder
{
    public function run(): void
    {
        // Initial seeders + dummy data (projects, finance, payroll, etc.)
        (new AvsbSeeder)->run(dummy: true, bulk: false);
    }
}
