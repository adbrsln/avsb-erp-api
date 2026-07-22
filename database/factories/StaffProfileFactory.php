<?php

namespace Database\Factories;

use App\Models\StaffProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffProfile>
 */
class StaffProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(['male', 'female']);
        $name = fake()->name($gender === 'male' ? 'ms_MY' : 'ms_MY');

        return [
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'phone' => '012-'.fake()->numerify('#######'),
            'employee_id' => 'EMP-'.fake()->unique()->numerify('####'),
            'identification_no' => fake()->numerify('######-##-####'),
            'is_active' => true,
            'basic_salary' => fake()->numberBetween(3000, 15000),
            'date_of_birth' => fake()->date(max: '-22 years'),
            'gender' => $gender,
            'nationality' => 'Malaysian',
            'race' => fake()->randomElement(['Malay', 'Chinese', 'Indian', 'Other']),
            'residential_status' => 'resident',
            'marital_status' => fake()->randomElement(['single', 'married']),
            'hire_date' => fake()->date(),
            'salary_wage_frequency' => 'monthly',
            'payment_method' => 'bank_transfer',
            'bank_name' => fake()->randomElement(['Maybank', 'CIMB', 'Public Bank', 'Hong Leong', 'RHB', 'Bank Islam']),
            'bank_account_no' => fake()->numerify('####-####-####'),
            'epf_contributing' => true,
            'eis_contributing' => fake()->boolean(80),
            'socso_contribution_type' => 'Automatic',
            'payroll_policy' => 'standard',
            'payroll_cycle' => 'monthly',
            'department' => fake()->randomElement(['Operations', 'Administration', 'Finance', 'HR', 'Engineering', 'Safety', 'Logistics', 'Procurement']),
            'location' => fake()->randomElement(['Kuala Lumpur HQ', 'Shah Alam Depot', 'Johor Branch', 'Penang Branch', 'Ipoh Depot']),
            'schedule' => 'Mon-Fri 9am-6pm',
            'created_at' => fake()->dateTimeBetween('-2 years'),
            'updated_at' => now(),
        ];
    }
}
