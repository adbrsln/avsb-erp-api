<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientPIC;
use App\Services\NumberingService;
use Illuminate\Support\Facades\DB;

class BulkClientSeeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Client::truncate();
        ClientPIC::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $numService = new NumberingService;

        $clients = Client::factory()
            ->count(150)
            ->sequence(function () use ($numService) {
                return ['client_code' => $numService->generate('client')];
            })
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
