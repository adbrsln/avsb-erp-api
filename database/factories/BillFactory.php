<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bill>
 */
class BillFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 1000, 100000);
        $tax = round($subtotal * 0.08, 2);
        $total = round($subtotal + $tax, 2);

        return [
            'bill_number' => 'BILL-'.fake()->unique()->numerify('######'),
            'vendor_id' => Vendor::factory(),
            'vendor_bill_no' => 'VENDOR-INV-'.fake()->numerify('#####'),
            'bill_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'due_date' => fn (array $attrs) => fake()->dateTimeBetween($attrs['bill_date'], '+60 days'),
            'status' => fake()->randomElement(['pending', 'paid', 'cancelled']),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'paid_amount' => 0,
            'balance' => $total,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
