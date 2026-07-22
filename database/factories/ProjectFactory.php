<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['TNB Ampang Cable', 'MRSM Gombak Road', 'Klang Valley Pothole', 'Jalan Ipoh Resurfacing', 'KLCC Marking', 'Lebuhraya Utara Milling', 'Shah Alam Drainage', 'Putrajaya Maintenance', 'Cyberjaya Road Marking', 'Seremban Highway Repair']).' '.fake()->year(),
            'project_code' => 'AVSB-'.fake()->unique()->bothify('??-####'),
            'client' => fake()->company(),
            'client_id' => Client::factory(),
            'location' => fake()->randomElement(['Kuala Lumpur', 'Shah Alam', 'Johor Bahru', 'Penang', 'Ipoh', 'Seremban', 'Melaka', 'Kuantan', 'Kota Kinabalu', 'Kuching']),
            'status' => fake()->randomElement(['active', 'completed', 'on_hold']),
            'budget_amount' => fake()->numberBetween(50000, 2000000),
            'start_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'end_date' => fn (array $attrs) => fake()->dateTimeBetween($attrs['start_date'], '+12 months'),
        ];
    }
}
