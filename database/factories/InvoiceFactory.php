<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10000, 500000);
        $sstRate = 8.0;
        $sst = round($subtotal * $sstRate / 100, 2);
        $retention = fake()->randomFloat(2, 0, round($subtotal * 0.1, 2));
        $total = round($subtotal + $sst, 2);

        return [
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
            'due_date' => fn (array $attrs) => fake()->dateTimeBetween($attrs['date'], '+60 days'),
            'client' => fake()->company(),
            'status' => fake()->randomElement(['unpaid', 'paid', 'overdue', 'partially_paid']),
            'subtotal' => $subtotal,
            'sst' => $sst,
            'retention' => $retention,
            'total' => $total,
            'items' => [
                ['description' => 'Milling & Paving Works', 'quantity' => 1, 'unit_price' => $subtotal * 0.7, 'total' => $subtotal * 0.7, 'unit' => 'lumpsum', 'tax_code' => '11'],
                ['description' => 'Road Marking Works', 'quantity' => 1, 'unit_price' => $subtotal * 0.3, 'total' => $subtotal * 0.3, 'unit' => 'lumpsum', 'tax_code' => '11'],
            ],
            'einvoice_notes' => fake()->optional()->sentence(),
        ];
    }
}
