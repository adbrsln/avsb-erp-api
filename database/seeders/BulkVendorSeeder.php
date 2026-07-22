<?php

namespace Database\Seeders;

use App\Helpers\MalaysianDataGenerator as G;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class BulkVendorSeeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Vendor::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $batch = [];
        for ($i = 0; $i < 150; $i++) {
            $name = G::randomCompany();
            $batch[] = [
                'vendor_code' => 'V-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'company_name' => $name,
                'registration_no' => rand(100000, 999999).'-'.chr(rand(65, 90)),
                'phone' => G::randomPhone(),
                'email' => G::randomEmail($name),
                'address' => G::randomLocation().', '.G::randomLocation(),
                'contact_person' => G::randomName(),
                'payment_terms' => ['14 days', '30 days', '45 days', '60 days'][array_rand([0, 1, 2, 3])],
                'status' => 'active',
            ];
        }

        foreach (array_chunk($batch, 50) as $chunk) {
            Vendor::insert($chunk);
        }
    }
}
