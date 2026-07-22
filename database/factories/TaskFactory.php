<?php

namespace Database\Factories;

use App\Models\Phase;
use App\Models\StaffProfile;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+1 week');

        return [
            'phase_id' => Phase::factory(),
            'title' => fake()->randomElement([
                'Mobilise crew & equipment',
                'Traffic management setup',
                'Cutting & milling surface',
                'Sweep & clean surface',
                'Prime coat application',
                'Tack coat spraying',
                'Asphalt paving',
                'Rolling compaction',
                'Joint cutting',
                'Thermoplastic marking',
                'Glass bead application',
                'Quality control sampling',
                'Site clearance',
                'Documentation sign-off',
                'Final inspection',
            ]),
            'status' => fake()->randomElement(['todo', 'running', 'paused', 'completed']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'assigned_to' => StaffProfile::factory(),
            'start_date' => $startDate,
            'end_date' => fn (array $attrs) => fake()->dateTimeBetween($attrs['start_date'], '+3 weeks'),
        ];
    }
}
