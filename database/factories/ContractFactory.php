<?php

namespace Database\Factories;

use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50000, 2000000);
        $sstRate = 8.0;
        $sst = round($subtotal * $sstRate / 100, 2);
        $retentionRate = fake()->randomElement([0, 5, 10]);
        $retention = $retentionRate > 0 ? round($subtotal * $retentionRate / 100, 2) : 0;
        $total = round($subtotal + $sst, 2);

        return [
            'contract_number' => 'C-'.fake()->unique()->numerify('####'),
            'client' => fake()->company(),
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
            'status' => fake()->randomElement(['draft', 'active', 'completed', 'terminated']),
            'total_amount' => $total,
            'subtotal' => $subtotal,
            'sst_rate' => $sstRate,
            'retention_rate' => $retentionRate,
            'terms' => fake()->paragraph(),
            'billing_milestones' => [
                ['description' => 'Mobilisation', 'percentage' => 20, 'amount' => round($total * 0.2, 2), 'due' => 'on_sign'],
                ['description' => 'Site Progress 50%', 'percentage' => 30, 'amount' => round($total * 0.3, 2), 'due' => 'on_progress'],
                ['description' => 'Site Progress 100%', 'percentage' => 30, 'amount' => round($total * 0.3, 2), 'due' => 'on_completion'],
                ['description' => 'Retention Release', 'percentage' => 20, 'amount' => round($total * 0.2, 2), 'due' => 'on_defects_liability'],
            ],
            'items' => [
                ['description' => 'Road works', 'quantity' => 1, 'unit_price' => $subtotal, 'total' => $subtotal, 'type' => 'service'],
            ],
        ];
    }
}
