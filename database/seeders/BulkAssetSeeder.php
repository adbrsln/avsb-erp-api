<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetLicense;
use App\Models\AssetMovement;
use App\Models\AssetService;
use App\Models\StaffProfile;

class BulkAssetSeeder
{
    public function run(): void
    {
        $staff = StaffProfile::first() ?? StaffProfile::factory()->create();
        $locations = ['Shah Alam Depot', 'Johor Depot', 'Penang Depot', 'Kuala Lumpur Yard', 'Ipoh Yard'];

        Asset::factory()
            ->count(50)
            ->sequence(function () use ($locations, $staff) {
                $cost = fake()->randomFloat(2, 3000, 3000000);

                return [
                    'purchase_cost' => $cost,
                    'current_value' => round($cost * fake()->randomFloat(2, 0.5, 0.9), 2),
                    'location' => $locations[array_rand($locations)],
                    'assigned_to' => $staff->id,
                    'created_by' => $staff->id,
                ];
            })
            ->create()
            ->each(function (Asset $asset) use ($staff, $locations) {
                if ($asset->asset_type === 'Vehicle') {
                    AssetLicense::factory()->create([
                        'asset_id' => $asset->id,
                        'license_number' => ($asset->registration_number ?? 'XXX').'-RT',
                        'issuing_authority' => 'JPJ Malaysia',
                    ]);
                }

                AssetService::create([
                    'asset_id' => $asset->id,
                    'service_type' => 'routine_maintenance',
                    'service_date' => fake()->dateTimeBetween('2024-01-01', '2024-06-30'),
                    'next_service_date' => fake()->dateTimeBetween('2024-07-01', '2024-12-31'),
                    'cost' => fake()->randomFloat(2, 200, 5000),
                    'vendor' => fake()->company(),
                    'description' => 'Scheduled maintenance',
                ]);

                if (rand(1, 3) === 1) {
                    AssetMovement::create([
                        'asset_id' => $asset->id,
                        'movement_type' => fake()->randomElement(['assignment', 'transfer', 'return']),
                        'from_location' => $locations[array_rand($locations)],
                        'to_location' => $locations[array_rand($locations)],
                        'movement_date' => fake()->dateTimeBetween('2024-01-01', '2024-06-30'),
                        'created_by' => $staff->id,
                    ]);
                }
            });
    }
}
