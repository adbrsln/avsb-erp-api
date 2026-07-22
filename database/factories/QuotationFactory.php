<?php

namespace Database\Factories;

use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10000, 500000);
        $sstRate = 8.0;
        $sst = round($subtotal * $sstRate / 100, 2);
        $total = round($subtotal + $sst, 2);

        return [
            'quote_number' => 'Q-'.fake()->unique()->numerify('####'),
            'client' => fake()->company(),
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
            'valid_until' => fn (array $attrs) => fake()->dateTimeBetween($attrs['date'], '+90 days'),
            'status' => fake()->randomElement(['draft', 'submitted', 'accepted', 'declined', 'expired']),
            'subtotal' => $subtotal,
            'sst' => $sst,
            'sst_rate' => $sstRate,
            'retention_pct' => fake()->randomFloat(2, 0, 10),
            'retention_amount' => 0,
            'total' => $total,
            'items' => [
                ['description' => 'Milling works - 5000m²', 'quantity' => 1, 'unit_price' => $subtotal * 0.6, 'total' => $subtotal * 0.6, 'unit' => 'lumpsum', 'type' => 'service'],
                ['description' => 'Paving works - 5000m²', 'quantity' => 1, 'unit_price' => $subtotal * 0.4, 'total' => $subtotal * 0.4, 'unit' => 'lumpsum', 'type' => 'service'],
            ],
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
