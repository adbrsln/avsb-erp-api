<?php

namespace Database\Factories;

use App\Models\FiscalPeriod;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FiscalPeriod>
 */
class FiscalPeriodFactory extends Factory
{
    public function definition(): array
    {
        $year = fake()->numberBetween(2024, 2027);
        $month = fake()->numberBetween(1, 12);
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        return [
            'name' => $startDate->format('F Y'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => 'monthly',
            'status' => fake()->randomElement(['open', 'closed']),
        ];
    }
}
