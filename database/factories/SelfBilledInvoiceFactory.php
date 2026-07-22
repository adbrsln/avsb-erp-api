<?php

namespace Database\Factories;

use App\Models\SelfBilledInvoice;
use App\Models\StaffProfile;
use App\Models\Subcontractor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SelfBilledInvoice>
 */
class SelfBilledInvoiceFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 5000, 200000);
        $sst = round($subtotal * 0.08, 2);
        $total = round($subtotal + $sst, 2);

        return [
            'invoice_number' => 'SBI-'.fake()->unique()->numerify('######'),
            'supplier_id' => Subcontractor::factory(),
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
            'due_date' => fn (array $attrs) => fake()->dateTimeBetween($attrs['date'], '+60 days'),
            'supply_date' => fake()->dateTimeBetween('-4 months', '-1 week'),
            'status' => fake()->randomElement(['draft', 'submitted', 'approved', 'rejected', 'paid']),
            'subtotal' => $subtotal,
            'sst' => $sst,
            'retention' => fake()->randomFloat(2, 0, round($subtotal * 0.1, 2)),
            'total' => $total,
            'items' => [
                ['description' => 'Subcon paving works Jan 2026', 'quantity' => 1, 'unit_price' => $subtotal, 'total' => $subtotal, 'unit' => 'lumpsum'],
            ],
            'notes' => fake()->optional()->sentence(),
            'created_by' => StaffProfile::factory(),
        ];
    }
}
