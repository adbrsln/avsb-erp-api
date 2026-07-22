<?php

namespace Database\Seeders;

use App\Helpers\MalaysianDataGenerator as G;
use App\Models\Client;
use App\Models\ClientPIC;
use Illuminate\Database\Capsule\Manager as Capsule;

class BulkClientSeeder
{
    public function run(): void
    {
        Capsule::connection()->statement('SET FOREIGN_KEY_CHECKS = 0');
        Client::truncate();
        ClientPIC::truncate();
        Capsule::connection()->statement('SET FOREIGN_KEY_CHECKS = 1');

        $batch = [];
        $usedNames = [];
        for ($i = 0; $i < 150; $i++) {
            do {
                $name = G::randomCompany();
            } while (in_array($name, $usedNames));
            $usedNames[] = $name;
            $batch[] = [
                'client_code' => 'CLT-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'company_name' => $name,
                'registration_no' => rand(100000, 999999).'-'.chr(rand(65, 90)),
                'phone' => G::randomPhone(),
                'email' => G::randomEmail($name),
                'address' => G::randomLocation().', '.G::randomLocation(),
                'billing_address' => G::randomLocation().', '.G::randomLocation(),
                'sst_reg_no' => 'SST-'.str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
            ];
        }

        foreach (array_chunk($batch, 50) as $chunk) {
            Client::insert($chunk);
        }

        // Create PICs for each client
        $picsBatch = [];
        foreach (Client::all() as $client) {
            $numPics = rand(1, 3);
            for ($j = 0; $j < $numPics; $j++) {
                $name = G::randomName();
                $picsBatch[] = [
                    'client_id' => $client->id,
                    'name' => $name,
                    'email' => G::randomEmail($name),
                    'phone' => G::randomPhone(),
                    'job_title' => ['Director', 'Project Manager', 'Procurement Officer', 'Engineer', 'Contract Manager', 'Finance Manager'][array_rand([0, 1, 2, 3, 4, 5])],
                    'department' => ['Procurement', 'Projects', 'Engineering', 'Finance', 'Operations'][array_rand([0, 1, 2, 3, 4])],
                    'is_primary' => $j === 0,
                ];
            }
        }

        foreach (array_chunk($picsBatch, 100) as $chunk) {
            ClientPIC::insert($chunk);
        }
    }
}
