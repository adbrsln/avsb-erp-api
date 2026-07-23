<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Services\NumberingService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'vendor_code' => app(NumberingService::class)->generate('vendor'),
            'company_name' => $name,
            'registration_no' => fake()->numerify('########-X'),
            'tax_id' => 'TIN'.fake()->numerify('########'),
            'phone' => '03-'.fake()->numerify('########'),
            'email' => 'sales@'.strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)).'.com.my',
            'address' => fake()->address(),
            'payment_terms' => fake()->randomElement(['30 days', '60 days', 'COD']),
            'contact_person' => fake()->name('ms_MY'),
            'status' => 'active',
        ];
    }
}
