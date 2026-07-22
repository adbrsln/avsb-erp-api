<?php

namespace App\Seeds;

use App\Helpers\MalaysianDataGenerator as G;
use App\Models\Asset;
use App\Models\AssetLicense;
use App\Models\AssetMovement;
use App\Models\AssetService;

class BulkAssetSeeder
{
    public function run(): void
    {
        $assetTypes = [
            'heavy_equipment' => [
                ['make' => 'Dynapac', 'models' => ['SD2500C', 'SD1800W', 'CA602']],
                ['make' => 'Bomag', 'models' => ['BW215D-5', 'BW120AD-5', 'BW203AD-5']],
                ['make' => 'Hamm', 'models' => ['HD+ 140i', 'HD+ 90i', 'DV+ 90i']],
                ['make' => 'Wirtgen', 'models' => ['W200i', 'W130i', 'W50i']],
                ['make' => 'Caterpillar', 'models' => ['CB64', 'CB44B', 'CS56']],
            ],
            'vehicle' => [
                ['make' => 'Toyota', 'models' => ['Hilux 2.8G', 'Hilux 2.4G', 'Fortuner 2.8V']],
                ['make' => 'Mitsubishi', 'models' => ['Triton 2.4L', 'Triton 3.2L', 'Pajero Sport']],
                ['make' => 'Isuzu', 'models' => ['D-Max 3.0L', 'D-Max 1.9L', 'NPR Tipper']],
                ['make' => 'Ford', 'models' => ['Ranger 2.0L', 'Ranger 3.2L', 'Transit Van']],
                ['make' => 'Scania', 'models' => ['G460', 'G410', 'P360']],
                ['make' => 'Hino', 'models' => ['700 FM', '500 FG', '300 Series']],
            ],
            'equipment' => [
                ['make' => 'Wacker Neuson', 'models' => ['DPU130', 'DPU100', 'BS60-4i']],
                ['make' => 'Mikasa', 'models' => ['MVD-80D', 'MVH-80D', 'MTA-60']],
                ['make' => 'Honda', 'models' => ['GX160', 'GX390', 'EB3000']],
                ['make' => 'Yanmar', 'models' => ['YMG200', 'YMG150', 'YSR150']],
            ],
            'it_equipment' => [
                ['make' => 'Dell', 'models' => ['PowerEdge R740', 'OptiPlex 7080', 'Latitude 5540']],
                ['make' => 'Lenovo', 'models' => ['ThinkPad X1', 'ThinkCentre M90', 'IdeaPad 5']],
                ['make' => 'HP', 'models' => ['ProBook 450', 'EliteBook 840', 'ProDesk 400']],
                ['make' => 'Cisco', 'models' => ['Catalyst 2960', 'ISR 4321', 'ASA 5506']],
            ],
        ];

        $statuses = ['active', 'active', 'active', 'inactive', 'under_repair', 'active'];
        $conditions = ['excellent', 'good', 'good', 'good', 'fair', 'excellent'];

        for ($i = 0; $i < 50; $i++) {
            $type = array_rand($assetTypes);
            $info = $assetTypes[$type][array_rand($assetTypes[$type])];
            $model = $info['models'][array_rand($info['models'])];
            $year = rand(2018, 2024);
            $cost = $type === 'heavy_equipment' ? G::randomAmount(300000, 3000000, 0) :
                    ($type === 'vehicle' ? G::randomAmount(80000, 600000, 0) :
                    ($type === 'equipment' ? G::randomAmount(10000, 500000, 0) :
                     G::randomAmount(3000, 100000, 0)));

            $asset = Asset::create([
                'name' => $info['make'].' '.$model,
                'asset_type' => $type,
                'make' => $info['make'],
                'model' => $model,
                'year' => $year,
                'serial_number' => strtoupper(substr($info['make'], 0, 3)).'-'.$model.'-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'registration_number' => in_array($type, ['vehicle']) ? ('B'.chr(rand(65, 90)).chr(rand(65, 90)).' '.rand(1000, 9999)) : null,
                'specifications' => json_encode(['year' => $year, 'status' => 'operational']),
                'purchase_date' => G::randomDate('2020-01-01', '2024-06-01'),
                'purchase_cost' => $cost,
                'current_value' => round($cost * (0.5 + (rand(0, 10000) / 10000) * 0.4)),
                'status' => $statuses[array_rand($statuses)],
                'condition' => $conditions[array_rand($conditions)],
                'location' => G::randomLocation(),
                'notes' => $type.' asset for project operations',
            ]);

            // License for vehicles
            if ($type === 'vehicle') {
                AssetLicense::create([
                    'asset_id' => $asset->id,
                    'license_type' => 'road_tax',
                    'license_number' => $asset->registration_number.'-RT',
                    'issuing_authority' => 'JPJ Malaysia',
                    'issue_date' => G::randomDate('2024-01-01', '2024-06-01'),
                    'expiry_date' => G::randomDate('2024-07-01', '2025-06-30'),
                    'cost' => G::randomAmount(500, 3000),
                    'status' => 'active',
                ]);
            }

            // Service record
            AssetService::create([
                'asset_id' => $asset->id,
                'service_type' => 'routine_maintenance',
                'service_date' => G::randomDate('2024-01-01', '2024-06-30'),
                'next_service_date' => G::randomDate('2024-07-01', '2024-12-31'),
                'cost' => G::randomAmount(200, 5000),
                'vendor' => G::randomCompany(),
                'description' => 'Scheduled maintenance',
            ]);

            // Movement for some assets
            if (rand(1, 3) === 1) {
                AssetMovement::create([
                    'asset_id' => $asset->id,
                    'movement_type' => ['assignment', 'transfer', 'return'][array_rand([0, 1, 2])],
                    'from_location' => G::randomLocation(),
                    'to_location' => G::randomLocation(),
                    'movement_date' => G::randomDate('2024-01-01', '2024-06-30'),
                    'created_by' => 1,
                ]);
            }
        }
    }
}
