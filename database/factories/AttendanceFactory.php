<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\StaffProfile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-2 months', 'now');
        $clockIn = Carbon::instance($date)->setTime(8, fake()->numberBetween(0, 30), 0);
        $clockOut = Carbon::instance($date)->setTime(17, fake()->numberBetween(0, 30), 0);
        $totalHours = round($clockIn->diffInMinutes($clockOut) / 60, 1);

        return [
            'staff_id' => StaffProfile::factory(),
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'total_hours' => $totalHours,
            'clock_in_latitude' => fake()->latitude(2.5, 3.5),
            'clock_in_longitude' => fake()->longitude(101.5, 102.0),
            'clock_out_latitude' => fake()->latitude(2.5, 3.5),
            'clock_out_longitude' => fake()->longitude(101.5, 102.0),
            'clock_in_ip' => fake()->localIpv4(),
            'clock_out_ip' => fake()->localIpv4(),
            'status' => 'present',
            'flagged' => false,
            'note' => null,
        ];
    }

    public function flagged(): static
    {
        return $this->state(fn (array $attributes) => [
            'flagged' => true,
            'flagged_reason' => fake()->randomElement(['Late clock in', 'Early clock out', 'Missing clock out']),
        ]);
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'clock_in' => null,
            'clock_out' => null,
            'total_hours' => 0,
            'status' => 'absent',
        ]);
    }
}
