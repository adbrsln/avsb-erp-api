<?php

namespace Database\Factories;

use App\Models\Subcontractor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subcontractor>
 */
class SubcontractorFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'subcontractor_code' => 'SUB-'.fake()->unique()->numerify('####'),
            'company_name' => $name,
            'registration_no' => fake()->numerify('########-X'),
            'tax_id' => 'TIN'.fake()->numerify('########'),
            'sst_reg_no' => 'SST-'.fake()->numerify('########'),
            'phone' => '03-'.fake()->numerify('########'),
            'email' => 'admin@'.strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)).'.com.my',
            'address' => fake()->address(),
            'status' => 'active',
            'cidb_reg_no' => 'CIDB-'.fake()->numerify('####-####-####'),
            'cidb_grade' => fake()->randomElement(['G1', 'G2', 'G3', 'G4', 'G5', 'G6', 'G7']),
            'cidb_expiry' => fake()->dateTimeBetween('now', '+3 years'),
            'licenses' => [
                ['type' => 'cidb', 'grade' => 'G7', 'expiry' => '2027-12-31'],
            ],
            'insurances' => [
                ['type' => 'public_liability', 'provider' => 'Zurich', 'coverage' => 1000000, 'expiry' => '2027-06-30'],
            ],
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
