<?php

namespace App\Seeds;

use App\Models\LeaveApplication;
use App\Models\StaffProfile;

class StaffLeaveSeeder
{
    public function run(): void
    {
        $staff = StaffProfile::all();
        if ($staff->isEmpty()) {
            return;
        }

        $leaves = [
            ['staff_idx' => 1, 'type' => 'annual', 'start' => '2024-06-10', 'end' => '2024-06-12', 'reason' => 'Hari Raya celebration', 'status' => 'approved'],
            ['staff_idx' => 1, 'type' => 'medical', 'start' => '2024-05-20', 'end' => '2024-05-21', 'reason' => 'Medical checkup', 'status' => 'approved'],
            ['staff_idx' => 2, 'type' => 'annual', 'start' => '2024-06-15', 'end' => '2024-06-19', 'reason' => 'Family vacation', 'status' => 'pending'],
            ['staff_idx' => 2, 'type' => 'medical', 'start' => '2024-04-22', 'end' => '2024-04-23', 'reason' => 'Sick leave', 'status' => 'approved'],
            ['staff_idx' => 3, 'type' => 'annual', 'start' => '2024-05-01', 'end' => '2024-05-01', 'reason' => 'Personal matter', 'status' => 'pending'],
            ['staff_idx' => 3, 'type' => 'unpaid', 'start' => '2024-06-20', 'end' => '2024-06-21', 'reason' => 'Urgent personal affairs', 'status' => 'pending'],
            ['staff_idx' => 4, 'type' => 'annual', 'start' => '2024-07-08', 'end' => '2024-07-12', 'reason' => 'Year-end leave', 'status' => 'pending'],
            ['staff_idx' => 4, 'type' => 'emergency', 'start' => '2024-03-10', 'end' => '2024-03-10', 'reason' => 'Family emergency', 'status' => 'approved'],
            ['staff_idx' => 0, 'type' => 'annual', 'start' => '2024-06-01', 'end' => '2024-06-05', 'reason' => 'Personal leave', 'status' => 'approved'],
            ['staff_idx' => 0, 'type' => 'medical', 'start' => '2024-04-15', 'end' => '2024-04-16', 'reason' => 'Medical appointment', 'status' => 'approved'],
            ['staff_idx' => 1, 'type' => 'marriage', 'start' => '2024-08-01', 'end' => '2024-08-03', 'reason' => 'Wedding leave', 'status' => 'pending'],
            ['staff_idx' => 3, 'type' => 'annual', 'start' => '2024-07-01', 'end' => '2024-07-02', 'reason' => 'Personal leave', 'status' => 'approved'],
            ['staff_idx' => 2, 'type' => 'emergency', 'start' => '2024-07-15', 'end' => '2024-07-15', 'reason' => 'Family emergency', 'status' => 'pending'],
            ['staff_idx' => 4, 'type' => 'medical', 'start' => '2024-02-20', 'end' => '2024-02-21', 'reason' => 'MC - Fever', 'status' => 'rejected'],
            ['staff_idx' => 0, 'type' => 'unpaid', 'start' => '2024-09-01', 'end' => '2024-09-03', 'reason' => 'Extended personal leave', 'status' => 'pending'],
        ];

        $baseRef = 'LV-2024-';
        $refNum = 6;

        foreach ($leaves as $l) {
            $s = $staff->values()->get($l['staff_idx'] % $staff->count());
            if (! $s) {
                continue;
            }

            $approverId = $l['status'] === 'approved'
                ? $staff->where('id', '!=', $s->id)->first()?->id ?? 1
                : null;

            LeaveApplication::create([
                'leave_ref' => $baseRef.str_pad($refNum, 3, '0', STR_PAD_LEFT),
                'staff_id' => $s->id,
                'type' => $l['type'],
                'start_date' => $l['start'],
                'end_date' => $l['end'],
                'reason' => $l['reason'],
                'status' => $l['status'],
                'approver_id' => $approverId,
                'approved_at' => $approverId ? date('Y-m-d', strtotime($l['start'].' -3 days')).' 09:00:00' : null,
            ]);
            $refNum++;
        }
    }
}
