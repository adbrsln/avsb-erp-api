<?php

namespace App\Seeds;

use App\Models\Attendance;
use App\Models\Project;
use App\Models\StaffProfile;

class AttendanceSeeder
{
    public function run(): void
    {
        if (Attendance::count() > 0) {
            return;
        }

        $staff = StaffProfile::all();
        $projects = Project::all();

        $projects = $projects->isEmpty() ? collect([(object) ['id' => null]]) : $projects;
        $staff = $staff->isEmpty() ? collect([(object) ['id' => 1]]) : $staff;

        $projectIds = $projects->pluck('id')->toArray();
        $staffIds = $staff->pluck('id')->toArray();

        $statuses = ['present', 'present', 'present', 'present', 'late', 'present', 'present'];
        $batch = [];
        $now = time();

        foreach ($staffIds as $sIdx => $sid) {
            $baseStart = strtotime('2024-01-02');
            $daysPresent = 0;

            for ($d = 0; $d < 60; $d++) {
                $dateTs = strtotime("+{$d} day", $baseStart);
                $dow = (int) date('w', $dateTs);

                // Skip weekends
                if ($dow === 0 || $dow === 6) {
                    continue;
                }

                $date = date('Y-m-d', $dateTs);
                $status = $statuses[array_rand($statuses)];

                // Some staff skip some days (casual/medical leave)
                if (rand(1, 20) === 1 && $daysPresent > 5) {
                    continue;
                }
                $daysPresent++;

                $clockInHour = 7;
                $clockInMin = $status === 'late' ? rand(15, 90) : rand(0, 15);
                $totalHours = $status === 'late' ? rand(6, 8) : rand(8, 10);

                $clockIn = date('Y-m-d H:i:s', strtotime("{$date} ".($clockInHour + intdiv($clockInMin, 60)).':'.sprintf('%02d', $clockInMin % 60).':00'));
                $clockOut = date('Y-m-d H:i:s', strtotime($clockIn) + $totalHours * 3600 + rand(0, 1800));

                $pid = $projectIds[array_rand($projectIds)];
                $latitude = 3.1390 + (rand(-100, 100) / 10000);
                $longitude = 101.6869 + (rand(-100, 100) / 10000);

                $batch[] = [
                    'staff_id' => $sid,
                    'date' => $date,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'total_hours' => round($totalHours + (rand(0, 50) / 100), 2),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'clock_in_ip' => '192.168.1.'.rand(10, 200),
                    'status' => $status === 'late' ? 'present' : $status,
                    'note' => $status === 'late' ? 'Arrived late due to traffic' : null,
                    'project_id' => $pid,
                ];
            }
        }

        foreach (array_chunk($batch, 200) as $chunk) {
            Attendance::insert($chunk);
        }
    }
}
