<?php

namespace App\Seeds;

use App\Models\LeaveGroupEntitlement;
use App\Models\StaffLeaveBalance;
use App\Models\StaffProfile;

class LeaveBalanceSeeder
{
    public function run(): void
    {
        if (StaffLeaveBalance::count() > 0) {
            return;
        }

        $staff = StaffProfile::all();
        $year = date('Y');

        $entitlements = LeaveGroupEntitlement::all();
        $defaultTypes = ['annual' => 14, 'medical' => 14, 'emergency' => 3, 'maternity' => 60, 'paternity' => 7, 'unpaid' => 0, 'marriage' => 3];

        foreach ($staff as $s) {
            $staffEntitlements = $entitlements->where('leave_group_id', $s->leave_group_id);
            if ($staffEntitlements->isEmpty()) {
                foreach ($defaultTypes as $type => $days) {
                    if ($type === 'maternity' && $s->gender === 'male') {
                        continue;
                    }
                    if ($type === 'paternity' && $s->gender === 'female') {
                        continue;
                    }
                    StaffLeaveBalance::create([
                        'staff_id' => $s->id,
                        'type' => $type,
                        'year' => $year,
                        'entitled' => $days,
                        'used' => rand(0, 3),
                        'adjusted' => 0,
                        'balance' => $days - rand(0, 3),
                    ]);
                }
            } else {
                foreach ($staffEntitlements as $e) {
                    $used = rand(0, min(5, (int) $e->days_entitled));
                    StaffLeaveBalance::create([
                        'staff_id' => $s->id,
                        'type' => $e->type,
                        'year' => $year,
                        'entitled' => $e->days_entitled,
                        'used' => $used,
                        'adjusted' => 0,
                        'balance' => $e->days_entitled - $used,
                    ]);
                }
            }
        }
    }
}
