<?php

namespace Database\Factories;

use App\Models\ExpenseClaim;
use App\Models\StaffProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseClaim>
 */
class ExpenseClaimFactory extends Factory
{
    public function definition(): array
    {
        $items = [
            ['category' => 'Transport', 'description' => 'Mileage claim', 'amount' => fake()->randomFloat(2, 20, 500)],
            ['category' => 'Meals', 'description' => 'Site lunch', 'amount' => fake()->randomFloat(2, 10, 50)],
            ['category' => 'Supplies', 'description' => 'Safety boots', 'amount' => fake()->randomFloat(2, 50, 300)],
        ];
        $total = round(array_sum(array_column($items, 'amount')), 2);

        return [
            'claim_ref' => 'CLM-'.fake()->unique()->numerify('######'),
            'staff_id' => StaffProfile::factory(),
            'title' => fake()->randomElement(['Site transport claim', 'Equipment purchase', 'Travel reimbursement', 'Mileage claim']),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected', 'paid']),
            'total_amount' => $total,
            'submitted_date' => fake()->dateTimeBetween('-2 months', 'now'),
            'items' => $items,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending']);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approver_id' => StaffProfile::factory(),
            'approved_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->approved()->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => 'PAY-'.fake()->numerify('######'),
        ]);
    }
}
