<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\StaffProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    private static array $equipment = [
        'Excavator' => ['CAT', 'Komatsu', 'Hitachi', 'Hyundai'],
        'Compactor' => ['Bomag', 'Dynapac', 'Hamm', 'Sakai'],
        'Pneumatic Tyred Roller' => ['Dynapac', 'Bomag', 'INGERSOLL-RAND'],
        'Asphalt Paver' => ['Vögele', 'Dynapac', 'CAT', 'LeeBoy'],
        'Motor Grader' => ['CAT', 'Komatsu', 'John Deere'],
        'Bulldozer' => ['CAT', 'Komatsu', 'Shantui'],
        'Wheel Loader' => ['CAT', 'Komatsu', 'Kawasaki', 'XCMG'],
        'Road Milling Machine' => ['Wirtgen', 'Roadtec', 'Caterpillar'],
        'Dump Truck' => ['Isuzu', 'Hino', 'Scania', 'Volvo'],
        'Water Truck' => ['Isuzu', 'Hino', 'Mitsubishi'],
        'Skid Steer Loader' => ['Bobcat', 'Caterpillar', 'JCB'],
        'Mobile Crane' => ['Tadano', 'Kato', 'Liebherr'],
        'Concrete Mixer' => ['Isuzu', 'Hino', 'Mack'],
        'Road Marking Machine' => ['Titan', 'Graco', 'Hofmann'],
        'Crack Sealing Machine' => ['Cimline', 'Sealmaster', 'Stepp'],
    ];

    public function definition(): array
    {
        $name = fake()->randomElement(array_keys(self::$equipment));
        $make = fake()->randomElement(self::$equipment[$name]);
        $purchaseCost = fake()->randomFloat(2, 50000, 1500000);

        return [
            'asset_code' => 'AST-'.fake()->unique()->numerify('######'),
            'name' => $name,
            'asset_type' => fake()->randomElement(['Equipment', 'Vehicle', 'Tool']),
            'make' => $make,
            'model' => strtoupper(fake()->bothify('??####')),
            'year' => fake()->numberBetween(2018, 2026),
            'serial_number' => strtoupper(fake()->bothify('SN-########')),
            'registration_number' => fake()->optional()->regexify('[A-Z]{1,3}[0-9]{1,4}'),
            'specifications' => ['engine' => fake()->randomElement(['C4.4', '6HK1', 'QSB6.7']), 'weight_kg' => fake()->numberBetween(1000, 50000), 'fuel_type' => 'diesel'],
            'purchase_date' => fake()->dateTimeBetween('-5 years', '-1 month'),
            'purchase_cost' => $purchaseCost,
            'current_value' => fn (array $attrs) => round($attrs['purchase_cost'] * fake()->randomFloat(2, 0.3, 0.9), 2),
            'status' => fake()->randomElement(['available', 'assigned', 'in_use', 'maintenance', 'retired', 'disposed']),
            'condition' => fake()->randomElement(['new', 'good', 'fair', 'poor']),
            'warranty_expiry' => fake()->optional()->dateTimeBetween('now', '+2 years'),
            'location' => fake()->randomElement(['Shah Alam Depot', 'Johor Depot', 'Penang Depot', 'Kuala Lumpur Yard', 'Ipoh Yard']),
            'assigned_to' => StaffProfile::factory(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => StaffProfile::factory(),
        ];
    }
}
