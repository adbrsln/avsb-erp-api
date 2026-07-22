<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'client_code' => 'CLT-'.fake()->unique()->numerify('####'),
            'company_name' => $name,
            'registration_no' => fake()->numerify('########-X'),
            'phone' => '03-'.fake()->numerify('########'),
            'email' => 'info@'.strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)).'.com.my',
            'address' => fake()->address(),
            'billing_address' => fake()->address(),
            'tax_id' => 'TIN'.fake()->numerify('########'),
            'sst_reg_no' => 'SST-'.fake()->numerify('########'),
            'buyer_type' => fake()->randomElement(['company', 'individual']),
            'contact_phone' => '012-'.fake()->numerify('#######'),
            'notes' => null,
        ];
    }
}
