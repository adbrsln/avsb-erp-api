<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 1000, 100000);
        $tax = round($subtotal * 0.08, 2);

        return [
            'po_number' => 'PO-'.fake()->unique()->numerify('######'),
            'vendor_id' => Vendor::factory(),
            'order_date' => fake()->dateTimeBetween('-2 months', 'now'),
            'delivery_date' => fn (array $attrs) => fake()->dateTimeBetween($attrs['order_date'], '+30 days'),
            'status' => fake()->randomElement(['draft', 'pending', 'approved', 'received', 'cancelled']),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => round($subtotal + $tax, 2),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
