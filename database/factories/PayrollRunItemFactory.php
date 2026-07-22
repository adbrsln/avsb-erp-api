<?php

namespace Database\Factories;

use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use App\Models\StaffProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollRunItem>
 */
class PayrollRunItemFactory extends Factory
{
    public function definition(): array
    {
        $salary = fake()->randomFloat(2, 3000, 15000);

        return [
            'period_id' => PayrollPeriod::factory(),
            'employee_id' => StaffProfile::factory(),
            'salary' => $salary,
            'wage_type' => 'monthly',
            'total_hours' => 0,
            'hourly_rate_applied' => 0,
            'period_start' => fake()->dateTimeBetween('-2 months', '-1 month'),
            'period_end' => fake()->dateTimeBetween('-1 month', 'now'),
            'epf_employer' => round($salary * 0.12, 2),
            'epf_employee' => round($salary * 0.11, 2),
            'epf_schedule_code' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'socso_employer' => round($salary * 0.0175, 2),
            'socso_employee' => round($salary * 0.005, 2),
            'eis_employer' => round($salary * 0.002, 2),
            'eis_employee' => round($salary * 0.002, 2),
            'socso_24h_employee' => 0,
            'paid' => false,
            'confirmed' => false,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'confirmed' => true,
            'confirmed_at' => now(),
            'confirmed_by' => StaffProfile::factory(),
        ]);
    }

    public function paid(): static
    {
        return $this->confirmed()->state(fn (array $attributes) => [
            'paid' => true,
            'paid_at' => now(),
            'paid_by' => StaffProfile::factory(),
        ]);
    }
}
