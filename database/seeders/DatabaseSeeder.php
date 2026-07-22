<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $dummy = $this->command?->option('dummy') ?? false;
        $bulk = $this->command?->option('bulk') ?? false;

        // Delegate to the original AVSB-ERP orchestrator
        $seeder = new \App\DatabaseSeeder;
        $seeder->run($dummy, $bulk);
    }
}
