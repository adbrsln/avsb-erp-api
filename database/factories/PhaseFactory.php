<?php

namespace Database\Factories;

use App\Models\Phase;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Phase>
 */
class PhaseFactory extends Factory
{
    private static array $phaseNames = [
        'Site Visit & Assessment',
        'Mobilisation & Setup',
        'Road Preparation & Cleaning',
        'Milling Works',
        'Paving Works',
        'Road Marking',
        'Quality Control & Testing',
        'Beads Application',
        'Curing & Drying',
        'Final Inspection & Handover',
        'Documentation & Closeout',
        'Coring Test & Lab Report',
    ];

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-3 months', '+1 month');

        return [
            'project_id' => Project::factory(),
            'name' => fake()->randomElement(self::$phaseNames),
            'order' => fake()->numberBetween(1, 12),
            'start_date' => $startDate,
            'end_date' => fake()->dateTimeBetween($startDate, '+2 months'),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed']),
        ];
    }
}
