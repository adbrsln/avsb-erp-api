<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // AvsbSeeder is a plain class (not extending Seeder), run directly
        $dummy = false;
        $bulk = false;
        if ($this->command) {
            try {
                $dummy = (bool) $this->command->option('dummy');
            } catch (\Throwable) {
            }
            try {
                $bulk = (bool) $this->command->option('bulk');
            } catch (\Throwable) {
            }
        }
        (new AvsbSeeder)->run($dummy, $bulk);
    }
}
