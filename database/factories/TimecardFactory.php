<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\StaffProfile;
use App\Models\Timecard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Timecard>
 */
class TimecardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'staff_id' => StaffProfile::factory(),
            'project_id' => Project::factory(),
            'date' => fake()->dateTimeBetween('-2 weeks', 'now'),
            'hours_worked' => fake()->randomFloat(1, 2, 12),
            'description' => fake()->randomElement([
                'Road milling supervision',
                'Paving works execution',
                'Site preparation & cleaning',
                'Quality inspection',
                'Equipment maintenance',
                'Traffic management',
                'Material hauling & logistics',
            ]),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'approved']);
    }
}
