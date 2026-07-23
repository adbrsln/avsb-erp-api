<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientPIC;
use Illuminate\Support\Facades\DB;

class BulkClientSeeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Client::truncate();
        ClientPIC::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $clients = Client::factory()
            ->count(150)
            ->sequence(fn ($seq) => ['client_code' => 'CLT-'.str_pad($seq->index + 1, 4, '0', STR_PAD_LEFT)])
            ->create();

        foreach ($clients as $client) {
            $numPics = rand(1, 3);
            for ($j = 0; $j < $numPics; $j++) {
                ClientPIC::create([
                    'client_id' => $client->id,
                    'name' => fake()->name('ms_MY'),
                    'email' => fake()->safeEmail(),
                    'phone' => '012-'.fake()->numerify('#######'),
                    'job_title' => fake()->randomElement(['Director', 'Project Manager', 'Procurement Officer', 'Engineer', 'Contract Manager', 'Finance Manager']),
                    'department' => fake()->randomElement(['Procurement', 'Projects', 'Engineering', 'Finance', 'Operations']),
                    'is_primary' => $j === 0,
                ]);
            }
        }
    }
}
