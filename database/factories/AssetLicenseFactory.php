<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetLicense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetLicense>
 */
class AssetLicenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'license_type' => fake()->randomElement(['road_tax', 'insurance', 'permit', 'certification', 'registration']),
            'license_number' => strtoupper(fake()->bothify('LIC-########')),
            'issuing_authority' => fake()->randomElement(['JPJ', 'DOSH', 'CIDB', 'JKR', 'SPAD']),
            'issue_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'expiry_date' => fn (array $attrs) => fake()->dateTimeBetween($attrs['issue_date'], '+2 years'),
            'cost' => fake()->randomFloat(2, 100, 10000),
            'status' => fake()->randomElement(['active', 'expiring_soon', 'expired']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
