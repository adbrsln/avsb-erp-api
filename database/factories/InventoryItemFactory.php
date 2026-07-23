<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Services\NumberingService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    private static array $roadMaterials = [
        'ACWC-14' => ['Hot Mix Asphalt ACWC 14', 'tonne'],
        'ACBC-20' => ['Hot Mix Asphalt ACBC 20', 'tonne'],
        'PEMINDAH' => ['Prime Coat (MC-30)', 'litre'],
        'PELEKAT' => ['Tack Coat (RS-1K)', 'litre'],
        'BATA' => ['Interlocking Paver Block', 'unit'],
        'JALAN' => ['Road Base Granite Aggregate 20mm', 'tonne'],
        'BALDI' => ['Premixed Paint White', 'litre'],
        'MANIK' => ['Glass Beads Type A', 'kg'],
        'THINNER' => ['Thinner', 'litre'],
        'PRIMER' => ['Primer Paint', 'litre'],
        'THERMO' => ['Thermoplastic Paint White', 'kg'],
        'THERMOYEL' => ['Thermoplastic Paint Yellow', 'kg'],
        'SEAL' => ['Crack Sealant Hot Applied', 'kg'],
        'POLYFILL' => ['Joint Filler Board 12mm', 'sheet'],
        'PAVE' => ['Cold Mix Patching Material', 'bag'],
    ];

    public function definition(): array
    {
        $sku = fake()->randomElement(array_keys(self::$roadMaterials));
        [$name, $unit] = self::$roadMaterials[$sku];

        return [
            'sku' => app(NumberingService::class)->generate('inventory'),
            'name' => $name,
            'category' => fake()->randomElement(['Asphalt', 'Aggregate', 'Paint', 'Chemicals', 'Safety', 'Tools']),
            'unit' => $unit,
            'stock_qty' => fake()->numberBetween(0, 500),
            'unit_cost' => fake()->randomFloat(2, 10, 500),
            'reorder_level' => fake()->numberBetween(10, 100),
            'status' => 'active',
        ];
    }
}
