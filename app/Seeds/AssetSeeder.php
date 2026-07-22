<?php

namespace App\Seeds;

use App\Models\Asset;
use App\Models\AssetLicense;
use App\Models\AssetMovement;
use App\Models\AssetService;
use App\Models\StaffProfile;

class AssetSeeder
{
    public function run(): void
    {
        if (Asset::count() > 0) {
            return;
        }

        $staff = StaffProfile::all();
        $adminId = $staff->first()->id ?? 1;

        $assets = [
            // Paving Equipment
            ['name' => 'Dynapac SD2500C Paver', 'asset_type' => 'heavy_equipment', 'make' => 'Dynapac', 'model' => 'SD2500C', 'year' => 2020, 'serial_number' => 'DYN-SD25-001', 'registration_number' => null, 'specifications' => ['engine' => 'Cummins QSB6.7', 'power' => '164 kW', 'weight' => '18,000 kg', 'paving_width' => '2.55-9.0m'], 'purchase_date' => '2020-03-15', 'purchase_cost' => 1850000, 'current_value' => 1480000, 'status' => 'active', 'condition' => 'good', 'location' => 'Rawang Yard', 'assigned_to' => null, 'notes' => 'Main paver for highway projects'],
            ['name' => 'Vogele Super 1803-2 Paver', 'asset_type' => 'heavy_equipment', 'make' => 'Vogele', 'model' => 'Super 1803-2', 'year' => 2021, 'serial_number' => 'VOG-S1803-002', 'registration_number' => null, 'specifications' => ['engine' => 'Deutz TCD 2013', 'power' => '138 kW', 'weight' => '17,500 kg', 'paving_width' => '2.55-8.0m'], 'purchase_date' => '2021-06-20', 'purchase_cost' => 2100000, 'current_value' => 1785000, 'status' => 'active', 'condition' => 'excellent', 'location' => 'Shah Alam Depot', 'assigned_to' => null, 'notes' => 'Used for medium-width paving'],
            ['name' => 'Dynapac SD1800W Paver', 'asset_type' => 'heavy_equipment', 'make' => 'Dynapac', 'model' => 'SD1800W', 'year' => 2019, 'serial_number' => 'DYN-SD18-003', 'registration_number' => null, 'specifications' => ['engine' => 'Cummins QSB4.5', 'power' => '119 kW', 'weight' => '14,000 kg', 'paving_width' => '2.55-7.5m'], 'purchase_date' => '2019-11-01', 'purchase_cost' => 1650000, 'current_value' => 1155000, 'status' => 'active', 'condition' => 'good', 'location' => 'Rawang Yard', 'assigned_to' => null, 'notes' => null],

            // Rollers
            ['name' => 'Bomag BW215D-5 Tandem Roller', 'asset_type' => 'heavy_equipment', 'make' => 'Bomag', 'model' => 'BW215D-5', 'year' => 2022, 'serial_number' => 'BOM-BW215-001', 'registration_number' => null, 'specifications' => ['engine' => 'Deutz TD 3.6', 'power' => '74 kW', 'weight' => '12,500 kg', 'drum_width' => '2.0m'], 'purchase_date' => '2022-01-10', 'purchase_cost' => 890000, 'current_value' => 801000, 'status' => 'active', 'condition' => 'excellent', 'location' => 'Shah Alam Depot', 'assigned_to' => null, 'notes' => 'Primary breakdown roller'],
            ['name' => 'Hamm HD+ 140i Tandem Roller', 'asset_type' => 'heavy_equipment', 'make' => 'Hamm', 'model' => 'HD+ 140i', 'year' => 2020, 'serial_number' => 'HAM-HD140-002', 'registration_number' => null, 'specifications' => ['engine' => 'Cummins QSB3.3', 'power' => '55 kW', 'weight' => '7,500 kg', 'drum_width' => '1.5m'], 'purchase_date' => '2020-05-15', 'purchase_cost' => 650000, 'current_value' => 487500, 'status' => 'active', 'condition' => 'good', 'location' => 'Rawang Yard', 'assigned_to' => null, 'notes' => 'Intermediate rolling'],
            ['name' => 'Bomag BW120AD-5 Tandem Roller', 'asset_type' => 'heavy_equipment', 'make' => 'Bomag', 'model' => 'BW120AD-5', 'year' => 2019, 'serial_number' => 'BOM-BW120-003', 'registration_number' => null, 'specifications' => ['engine' => 'Kubota D1803', 'power' => '24 kW', 'weight' => '2,800 kg', 'drum_width' => '1.2m'], 'purchase_date' => '2019-08-20', 'purchase_cost' => 380000, 'current_value' => 266000, 'status' => 'active', 'condition' => 'fair', 'location' => 'Rawang Yard', 'assigned_to' => null, 'notes' => 'Small footprint, used for patches and tight areas'],
            ['name' => 'Dynapac CC6200 Pneumatic Roller', 'asset_type' => 'heavy_equipment', 'make' => 'Dynapac', 'model' => 'CC6200', 'year' => 2021, 'serial_number' => 'DYN-CC62-004', 'registration_number' => null, 'specifications' => ['engine' => 'Cummins QSB4.5', 'power' => '97 kW', 'weight' => '14,500 kg', 'tyres' => '9 front / 9 rear'], 'purchase_date' => '2021-03-01', 'purchase_cost' => 750000, 'current_value' => 637500, 'status' => 'active', 'condition' => 'excellent', 'location' => 'Shah Alam Depot', 'assigned_to' => null, 'notes' => 'Pneumatic tyre roller for final compaction'],

            // Milling Machines
            ['name' => 'Wirtgen W200i Cold Milling Machine', 'asset_type' => 'heavy_equipment', 'make' => 'Wirtgen', 'model' => 'W200i', 'year' => 2021, 'serial_number' => 'WIR-W200-001', 'registration_number' => null, 'specifications' => ['engine' => 'Cummins QSL9', 'power' => '298 kW', 'weight' => '30,000 kg', 'milling_width' => '2.0m'], 'purchase_date' => '2021-04-01', 'purchase_cost' => 2800000, 'current_value' => 2380000, 'status' => 'active', 'condition' => 'excellent', 'location' => 'Shah Alam Depot', 'assigned_to' => null, 'notes' => 'Large milling machine for highway work'],
            ['name' => 'Wirtgen W130i Cold Milling Machine', 'asset_type' => 'heavy_equipment', 'make' => 'Wirtgen', 'model' => 'W130i', 'year' => 2020, 'serial_number' => 'WIR-W130-002', 'registration_number' => null, 'specifications' => ['engine' => 'Cummins QSL9', 'power' => '221 kW', 'weight' => '22,000 kg', 'milling_width' => '1.3m'], 'purchase_date' => '2020-07-15', 'purchase_cost' => 2200000, 'current_value' => 1760000, 'status' => 'active', 'condition' => 'good', 'location' => 'Rawang Yard', 'assigned_to' => null, 'notes' => 'Medium milling machine for urban roads'],

            // Dump Trucks
            ['name' => 'Scania G460 Dump Truck', 'asset_type' => 'vehicle', 'make' => 'Scania', 'model' => 'G460', 'year' => 2022, 'serial_number' => 'SCA-G460-001', 'registration_number' => 'BXL 1234', 'specifications' => ['engine' => 'Scania DC13', 'power' => '460 hp', 'capacity' => '20 tonnes', 'axle' => '6x4'], 'purchase_date' => '2022-02-01', 'purchase_cost' => 520000, 'current_value' => 468000, 'status' => 'active', 'condition' => 'excellent', 'location' => 'Rawang Yard', 'assigned_to' => null, 'notes' => 'Primary haulage truck'],
            ['name' => 'Hino 700 FM Dump Truck', 'asset_type' => 'vehicle', 'make' => 'Hino', 'model' => '700 FM', 'year' => 2020, 'serial_number' => 'HIN-700FM-002', 'registration_number' => 'BXM 5678', 'specifications' => ['engine' => 'Hino E13C', 'power' => '380 hp', 'capacity' => '18 tonnes', 'axle' => '6x4'], 'purchase_date' => '2020-09-01', 'purchase_cost' => 450000, 'current_value' => 337500, 'status' => 'active', 'condition' => 'good', 'location' => 'Rawang Yard', 'assigned_to' => null, 'notes' => null],

            // Service Vehicles
            ['name' => 'Toyota Hilux 4x4 (Crew Cab)', 'asset_type' => 'vehicle', 'make' => 'Toyota', 'model' => 'Hilux 2.8G', 'year' => 2023, 'serial_number' => 'TOY-HILUX-001', 'registration_number' => 'BXN 9012', 'specifications' => ['engine' => '2.8L Diesel', 'power' => '204 hp', 'drive' => '4x4', 'color' => 'White'], 'purchase_date' => '2023-01-10', 'purchase_cost' => 148000, 'current_value' => 140600, 'status' => 'active', 'condition' => 'excellent', 'location' => 'Kuala Lumpur HQ', 'assigned_to' => $staff->where('email', 'ahmadi@example.com')->first()->id ?? 1, 'notes' => 'PM vehicle'],
            ['name' => 'Mitsubishi Triton 4x4', 'asset_type' => 'vehicle', 'make' => 'Mitsubishi', 'model' => 'Triton 2.4L', 'year' => 2022, 'serial_number' => 'MIT-TRITON-002', 'registration_number' => 'BXP 3456', 'specifications' => ['engine' => '2.4L Diesel', 'power' => '181 hp', 'drive' => '4x4', 'color' => 'Silver'], 'purchase_date' => '2022-06-15', 'purchase_cost' => 132000, 'current_value' => 118800, 'status' => 'active', 'condition' => 'good', 'location' => 'Shah Alam Depot', 'assigned_to' => null, 'notes' => 'Site supervisor vehicle'],

            // Generators
            ['name' => 'Caterpillar XQ330 Generator', 'asset_type' => 'equipment', 'make' => 'Caterpillar', 'model' => 'XQ330', 'year' => 2021, 'serial_number' => 'CAT-XQ330-001', 'registration_number' => null, 'specifications' => ['power_rating' => '330 kVA', 'fuel' => 'Diesel', 'sound_level' => '75 dBA', 'weight' => '4,500 kg'], 'purchase_date' => '2021-08-01', 'purchase_cost' => 420000, 'current_value' => 357000, 'status' => 'active', 'condition' => 'excellent', 'location' => 'Rawang Yard', 'assigned_to' => null, 'notes' => 'Backup power for critical operations'],
            ['name' => 'Denyo DLW-500ES Generator', 'asset_type' => 'equipment', 'make' => 'Denyo', 'model' => 'DLW-500ES', 'year' => 2020, 'serial_number' => 'DEN-DLW500-002', 'registration_number' => null, 'specifications' => ['power_rating' => '500 kVA', 'fuel' => 'Diesel', 'sound_level' => '70 dBA', 'weight' => '5,200 kg'], 'purchase_date' => '2020-04-01', 'purchase_cost' => 480000, 'current_value' => 384000, 'status' => 'active', 'condition' => 'good', 'location' => 'Shah Alam Depot', 'assigned_to' => null, 'notes' => null],

            // Plate Compactors & Misc
            ['name' => 'Wacker Neuson DPU130 Plate Compactor', 'asset_type' => 'equipment', 'make' => 'Wacker Neuson', 'model' => 'DPU130', 'year' => 2023, 'serial_number' => 'WAC-DPU130-001', 'registration_number' => null, 'specifications' => ['engine' => 'Hatz 1B40', 'power' => '8.4 kW', 'weight' => '600 kg', 'plate_size' => '900x600mm'], 'purchase_date' => '2023-02-15', 'purchase_cost' => 45000, 'current_value' => 42750, 'status' => 'active', 'condition' => 'new', 'location' => 'Rawang Yard', 'assigned_to' => null, 'notes' => 'Used for narrow trench compaction'],
            ['name' => 'Mikasa MVD-80D Plate Compactor', 'asset_type' => 'equipment', 'make' => 'Mikasa', 'model' => 'MVD-80D', 'year' => 2021, 'serial_number' => 'MIK-MVD80-002', 'registration_number' => null, 'specifications' => ['engine' => 'Kubota Z482', 'power' => '4.4 kW', 'weight' => '360 kg', 'plate_size' => '650x500mm'], 'purchase_date' => '2021-09-01', 'purchase_cost' => 28000, 'current_value' => 22400, 'status' => 'active', 'condition' => 'good', 'location' => 'Rawang Yard', 'assigned_to' => null, 'notes' => null],

            // IT Equipment
            ['name' => 'Dell PowerEdge R740 Server', 'asset_type' => 'it_equipment', 'make' => 'Dell', 'model' => 'PowerEdge R740', 'year' => 2024, 'serial_number' => 'DELL-R740-001', 'registration_number' => null, 'specifications' => ['cpu' => 'Xeon Silver 4214R', 'ram' => '128GB DDR4', 'storage' => '4x 2TB SSD RAID10', 'os' => 'Ubuntu Server 22.04'], 'purchase_date' => '2024-01-01', 'purchase_cost' => 85000, 'current_value' => 80750, 'status' => 'active', 'condition' => 'excellent', 'location' => 'Kuala Lumpur HQ', 'assigned_to' => null, 'notes' => 'ERP application server'],
            ['name' => 'Lenovo ThinkPad X1 Carbon Gen 11', 'asset_type' => 'it_equipment', 'make' => 'Lenovo', 'model' => 'ThinkPad X1 Carbon Gen 11', 'year' => 2024, 'serial_number' => 'LEN-X1C-001', 'registration_number' => null, 'specifications' => ['cpu' => 'i7-1365U', 'ram' => '32GB LPDDR5', 'storage' => '1TB NVMe', 'screen' => '14" 2.8K OLED'], 'purchase_date' => '2024-01-15', 'purchase_cost' => 9200, 'current_value' => 8740, 'status' => 'active', 'condition' => 'excellent', 'location' => 'Kuala Lumpur HQ', 'assigned_to' => $staff->first()->id ?? 1, 'notes' => 'Laptop for super admin'],

            // Additional vehicles
            ['name' => 'Isuzu D-MAX 4x4', 'asset_type' => 'vehicle', 'make' => 'Isuzu', 'model' => 'D-Max 3.0L', 'year' => 2021, 'serial_number' => 'ISU-DMAX-003', 'registration_number' => 'BXQ 7890', 'specifications' => ['engine' => '3.0L Diesel', 'power' => '190 hp', 'drive' => '4x4', 'color' => 'Blue'], 'purchase_date' => '2021-05-01', 'purchase_cost' => 125000, 'current_value' => 100000, 'status' => 'active', 'condition' => 'good', 'location' => 'Rawang Yard', 'assigned_to' => null, 'notes' => 'Site supervisor vehicle'],
            ['name' => 'Ford Ranger Wildtrak 4x4', 'asset_type' => 'vehicle', 'make' => 'Ford', 'model' => 'Ranger Wildtrak 2.0L', 'year' => 2023, 'serial_number' => 'FORD-RANGER-004', 'registration_number' => 'BXR 1234', 'specifications' => ['engine' => '2.0L Bi-Turbo Diesel', 'power' => '210 hp', 'drive' => '4x4', 'color' => 'Grey'], 'purchase_date' => '2023-06-01', 'purchase_cost' => 158000, 'current_value' => 150100, 'status' => 'active', 'condition' => 'excellent', 'location' => 'Kuala Lumpur HQ', 'assigned_to' => null, 'notes' => 'Director vehicle'],

            // Water Trucks
            ['name' => 'Mitsubishi Fuso FN527 Water Truck', 'asset_type' => 'vehicle', 'make' => 'Mitsubishi Fuso', 'model' => 'FN527', 'year' => 2020, 'serial_number' => 'FUS-FN527-001', 'registration_number' => 'BXS 5678', 'specifications' => ['engine' => '6M60', 'power' => '280 hp', 'capacity' => '8,000L', 'pump_flow' => '500 L/min'], 'purchase_date' => '2020-11-01', 'purchase_cost' => 380000, 'current_value' => 285000, 'status' => 'active', 'condition' => 'fair', 'location' => 'Rawang Yard', 'assigned_to' => null, 'notes' => 'Dust suppression water truck'],
        ];

        $createdAssets = [];
        foreach ($assets as $a) {
            $asset = Asset::create($a);
            $createdAssets[] = $asset;
        }

        // Licenses for vehicles
        $vehicleAssets = array_filter($createdAssets, fn ($a) => $a->asset_type === 'vehicle');
        foreach ($vehicleAssets as $va) {
            if ($va->registration_number) {
                AssetLicense::create([
                    'asset_id' => $va->id,
                    'license_type' => 'road_tax',
                    'license_number' => $va->registration_number.'-RT',
                    'issuing_authority' => 'JPJ Malaysia',
                    'issue_date' => '2024-01-01',
                    'expiry_date' => '2024-12-31',
                    'cost' => rand(800, 3000),
                    'status' => 'active',
                ]);
            }
        }

        // Licenses for heavy equipment (CIDB)
        $heavyAssets = array_filter($createdAssets, fn ($a) => $a->asset_type === 'heavy_equipment');
        foreach ($heavyAssets as $ha) {
            AssetLicense::create([
                'asset_id' => $ha->id,
                'license_type' => 'cidb',
                'license_number' => 'CIDB-'.$ha->serial_number,
                'issuing_authority' => 'CIDB Malaysia',
                'issue_date' => '2024-01-01',
                'expiry_date' => '2024-12-31',
                'cost' => rand(500, 1500),
                'status' => 'active',
            ]);
        }

        // Asset Movements (for a few assets)
        $assetsArray = $createdAssets;
        $movements = [
            ['asset' => $assetsArray[3] ?? null, 'type' => 'assignment', 'from' => 'Rawang Yard', 'to' => 'Jalan Tun Razak Site'],
            ['asset' => $assetsArray[4] ?? null, 'type' => 'assignment', 'from' => 'Rawang Yard', 'to' => 'Federal Highway Site'],
            ['asset' => $assetsArray[0] ?? null, 'type' => 'transfer', 'from' => 'Rawang Yard', 'to' => 'Shah Alam Depot'],
            ['asset' => $assetsArray[7] ?? null, 'type' => 'assignment', 'from' => 'Shah Alam Depot', 'to' => 'Putrajaya Site'],
        ];
        foreach ($movements as $m) {
            if ($m['asset']) {
                AssetMovement::create([
                    'asset_id' => $m['asset']->id,
                    'movement_type' => $m['type'],
                    'from_location' => $m['from'],
                    'to_location' => $m['to'],
                    'movement_date' => '2024-02-01',
                    'created_by' => $adminId,
                ]);
            }
        }

        // Services for heavy equipment and vehicles
        $serviceable = array_filter($createdAssets, fn ($a) => in_array($a->asset_type, ['heavy_equipment', 'vehicle', 'equipment']));
        foreach ($serviceable as $sa) {
            AssetService::create([
                'asset_id' => $sa->id,
                'service_type' => 'routine_maintenance',
                'service_date' => '2024-03-15',
                'next_service_date' => '2024-06-15',
                'cost' => rand(800, 5000),
                'vendor' => 'UEH Maintenance Services',
                'description' => 'Scheduled maintenance service',
            ]);
        }
    }
}
