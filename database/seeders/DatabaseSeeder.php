<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Check for custom options safely (may not exist in artisan db:seed)
        $dummy = false;
        $bulk = false;
        if ($this->command) {
            try {
                $dummy = (bool) $this->command->option('dummy');
            } catch (\Throwable) {
                // option doesn't exist on this command — ignore
            }
            try {
                $bulk = (bool) $this->command->option('bulk');
            } catch (\Throwable) {
                // option doesn't exist on this command — ignore
            }
        }

        // Delegate to the original AVSB-ERP orchestrator
        $seeder = new \App\DatabaseSeeder;
        $seeder->run($dummy, $bulk);
    }
}
