<?php

namespace Database\Factories;

use App\Models\LeaveApplication;
use App\Models\StaffProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveApplication>
 */
class LeaveApplicationFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+2 months');
        $endDate = fake()->dateTimeBetween($startDate, '+5 days');
        $type = fake()->randomElement(['annual', 'medical', 'unpaid', 'maternity', 'paternity', 'emergency']);

        return [
            'leave_ref' => 'LV-'.fake()->unique()->numerify('######'),
            'staff_id' => StaffProfile::factory(),
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_half_day' => fake()->boolean(10),
            'reason' => fake()->sentence(),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
        ];
    }
}
