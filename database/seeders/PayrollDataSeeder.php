<?php

namespace Database\Seeders;

use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use App\Models\StaffProfile;

class PayrollDataSeeder
{
    public function run(): void
    {
        if (PayrollPeriod::count() > 0) {
            return;
        }

        $staff = StaffProfile::all();
        if ($staff->isEmpty()) {
            return;
        }

        // 6 monthly payroll periods: Jan-Jun 2024
        $periods = [];
        for ($m = 1; $m <= 6; $m++) {
            $start = '2024-'.str_pad($m, 2, '0', STR_PAD_LEFT).'-01';
            $end = date('Y-m-t', strtotime($start));
            $periods[] = PayrollPeriod::create([
                'code' => '2024-'.str_pad($m, 2, '0', STR_PAD_LEFT),
                'month' => $m,
                'year' => 2024,
                'start_date' => $start,
                'end_date' => $end,
                'status' => $m <= 4 ? 'closed' : 'open',
            ]);
        }

        // Run items for Jan-May (closed months)
        $runningStaffId = 1;
        foreach ($staff as $s) {
            $salary = $s->basic_salary ?: 3000;
            $epfEmployee = round($salary * 0.11, 2);
            $epfEmployer = round($salary * 0.13, 2);
            $socsoEmployee = round(min($salary * 0.005, 49.75), 2);
            $socsoEmployer = round(min($salary * 0.0175, 87.35), 2);
            $eisEmployee = round(min($salary * 0.002, 7.40), 2);
            $eisEmployer = round(min($salary * 0.002, 7.40), 2);

            foreach ($periods as $pIdx => $period) {
                if ($period->status !== 'closed') {
                    continue;
                }

                $item = PayrollRunItem::create([
                    'period_id' => $period->id,
                    'employee_id' => $s->id,
                    'salary' => $salary,
                    'wage_type' => 'monthly',
                    'total_hours' => 208,
                    'hourly_rate_applied' => $salary / 208,
                    'period_start' => $period->start_date,
                    'period_end' => $period->end_date,
                    'epf_employer' => $epfEmployer,
                    'epf_employee' => $epfEmployee,
                    'epf_schedule_code' => 'A',
                    'socso_employer' => $socsoEmployer,
                    'socso_employee' => $socsoEmployee,
                    'eis_employer' => $eisEmployer,
                    'eis_employee' => $eisEmployee,
                    'socso_24h_employee' => 0,
                    'paid' => $pIdx <= 2,
                    'paid_at' => $pIdx <= 2 ? date('Y-m-d', strtotime($period->end_date.' +5 days')).' 10:00:00' : null,
                    'confirmed' => true,
                    'confirmed_at' => date('Y-m-d', strtotime($period->end_date.' +3 days')).' 14:00:00',
                    'confirmed_by' => 1,
                ]);

                // Add adjustment for some staff (overtime or deduction)
                if ($runningStaffId % 2 === 0) {
                    PayrollAdjustment::create([
                        'payroll_run_item_id' => $item->id,
                        'type' => 'earnings',
                        'label' => 'Overtime Allowance',
                        'amount' => rand(100, 500),
                        'created_by' => 1,
                    ]);
                }
                if ($runningStaffId % 3 === 0) {
                    PayrollAdjustment::create([
                        'payroll_run_item_id' => $item->id,
                        'type' => 'deductions',
                        'label' => 'Late Penalty',
                        'amount' => rand(20, 100),
                        'created_by' => 1,
                    ]);
                }
            }
            $runningStaffId++;
        }
    }
}
