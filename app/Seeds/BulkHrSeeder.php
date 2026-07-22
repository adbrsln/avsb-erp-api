<?php

namespace App\Seeds;

use App\Helpers\MalaysianDataGenerator as G;
use App\Models\Attendance;
use App\Models\ClaimItem;
use App\Models\ExpenseClaim;
use App\Models\LeaveApplication;
use App\Models\Project;
use App\Models\StaffProfile;
use App\Models\Timecard;

class BulkHrSeeder
{
    public function run(): void
    {
        $staff = StaffProfile::all();
        $projects = Project::all();

        if ($staff->isEmpty()) {
            return;
        }

        // ── 150 Leave Applications ──
        $leaveTypes = ['annual', 'medical', 'emergency', 'unpaid', 'annual', 'annual', 'medical', 'marriage', 'paternity'];
        $leaveStatuses = ['approved', 'approved', 'approved', 'pending', 'rejected', 'approved', 'pending'];
        $leaveReasons = ['Personal leave', 'Medical appointment', 'Family event', 'Hari Raya celebration', 'Vacation', 'Family emergency', 'Personal matters', 'Wedding', 'Child birth'];

        $leavesBatch = [];
        for ($i = 0; $i < 150; $i++) {
            $s = $staff->random();
            $type = $leaveTypes[array_rand($leaveTypes)];
            $status = $leaveStatuses[array_rand($leaveStatuses)];
            $startDate = G::randomDate('2024-01-01', '2024-12-31');
            $endDate = date('Y-m-d', strtotime($startDate.' +'.rand(0, 5).' days'));

            $leavesBatch[] = [
                'leave_ref' => 'LV-BULK-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'staff_id' => $s->id,
                'type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => $leaveReasons[array_rand($leaveReasons)],
                'status' => $status,
                'approver_id' => $status !== 'pending' ? $staff->where('id', '!=', $s->id)->first()?->id ?? 1 : null,
                'approved_at' => $status === 'approved' ? date('Y-m-d H:i:s', strtotime($startDate.' -3 days 09:00:00')) : null,
                'rejection_reason' => $status === 'rejected' ? 'Insufficient justification' : null,
                'created_at' => $startDate.' 08:00:00',
                'updated_at' => $startDate.' 08:00:00',
            ];
        }
        foreach (array_chunk($leavesBatch, 50) as $chunk) {
            LeaveApplication::insert($chunk);
        }

        // ── 150 Expense Claims with items ──
        $claimTitles = ['Fuel Reimbursement', 'Travel Expense', 'Office Supplies', 'Site Equipment', 'Training Fee', 'Accommodation', 'Toll & Parking', 'Mileage Claim', 'Safety Gear', 'Tool Purchase'];
        $categories = ['mileage', 'accommodation', 'toll', 'parking', 'office_supplies', 'training', 'entertainment', 'travel'];
        for ($i = 0; $i < 150; $i++) {
            $s = $staff->random();
            $total = G::randomAmount(50, 2000);
            $status = ['approved', 'submitted', 'approved', 'approved', 'rejected', 'submitted'][array_rand([0, 1, 2, 3, 4, 5])];
            $title = $claimTitles[array_rand($claimTitles)];

            $claim = ExpenseClaim::create([
                'claim_ref' => 'CLM-BULK-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'staff_id' => $s->id,
                'title' => $title.' - '.G::randomLocation(),
                'description' => $title.' for '.G::randomLocation().' site visit',
                'status' => $status,
                'total_amount' => $total,
                'submitted_date' => G::randomDate('2024-01-01', '2024-12-31'),
                'approver_id' => $status !== 'submitted' ? $staff->where('id', '!=', $s->id)->first()?->id ?? 1 : null,
                'approved_at' => $status === 'approved' ? date('Y-m-d H:i:s', strtotime(G::randomDate('2024-01-15', '2024-12-15').' 10:00:00')) : null,
            ]);

            // 1-3 items per claim
            for ($j = 0; $j < rand(1, 3); $j++) {
                ClaimItem::create([
                    'claim_id' => $claim->id,
                    'description' => $categories[array_rand($categories)].' expense item '.($j + 1),
                    'category' => $categories[array_rand($categories)],
                    'amount' => round($total / rand(1, 3), 2),
                ]);
            }
        }

        // ── Attendance (3 months for 50 staff = ~150 records) ──
        $attendanceBatch = [];
        $statusOptions = ['present', 'present', 'present', 'present', 'late', 'present'];
        $staffSubset = $staff->random(50);
        $baseStart = strtotime('2024-03-01');
        foreach ($staffSubset as $s) {
            for ($d = 0; $d < 30; $d++) {
                $dateTs = strtotime("+{$d} day", $baseStart);
                if (date('w', $dateTs) === '0' || date('w', $dateTs) === '6') {
                    continue;
                }
                if (rand(1, 15) === 1) {
                    continue;
                }

                $status = $statusOptions[array_rand($statusOptions)];
                $clockInHour = 7;
                $clockInMin = $status === 'late' ? rand(15, 90) : rand(0, 15);
                $totalHours = G::randomAmount(7, 10);
                $clockIn = date('Y-m-d H:i:s', mktime($clockInHour + intdiv($clockInMin, 60), $clockInMin % 60, 0, (int) date('m', $dateTs), (int) date('d', $dateTs), (int) date('Y', $dateTs)));
                $clockOut = date('Y-m-d H:i:s', strtotime($clockIn) + (int) ($totalHours * 3600));

                $attendanceBatch[] = [
                    'staff_id' => $s->id,
                    'date' => date('Y-m-d', $dateTs),
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'total_hours' => round($totalHours, 2),
                    'status' => $status === 'late' ? 'present' : $status,
                    'project_id' => $projects->random()->id ?? null,
                ];
            }
        }
        if (! empty($attendanceBatch)) {
            foreach (array_chunk($attendanceBatch, 100) as $chunk) {
                Attendance::insert($chunk);
            }
        }

        // ── 150 Timecards ──
        $timecardBatch = [];
        $descriptions = ['Site works', 'Quality inspection', 'Material handling', 'Equipment maintenance', 'Safety briefing', 'Survey works', 'Compaction works', 'Paving operations'];
        $tcStatuses = ['approved', 'approved', 'approved', 'pending', 'approved', 'rejected', 'approved'];
        for ($i = 0; $i < 150; $i++) {
            $s = $staff->random();
            $p = $projects->random();
            $timecardBatch[] = [
                'staff_id' => $s->id,
                'project_id' => $p->id ?? null,
                'date' => G::randomDate('2024-01-01', '2024-12-31'),
                'hours_worked' => G::randomAmount(6, 11),
                'description' => $descriptions[array_rand($descriptions)],
                'status' => $tcStatuses[array_rand($tcStatuses)],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }
        foreach (array_chunk($timecardBatch, 50) as $chunk) {
            Timecard::insert($chunk);
        }
    }
}
