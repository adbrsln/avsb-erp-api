<?php

namespace App\Seeds;

use App\Helpers\MalaysianDataGenerator as G;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use App\Models\StaffProfile;

class BulkPayrollSeeder
{
    public function run(): void
    {
        $staff = StaffProfile::all();
        if ($staff->isEmpty()) {
            return;
        }

        // 12 monthly periods
        $periods = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = '2024-'.str_pad($m, 2, '0', STR_PAD_LEFT).'-01';
            $end = date('Y-m-t', strtotime($start));
            $periods[] = PayrollPeriod::create([
                'code' => '2024-'.str_pad($m, 2, '0', STR_PAD_LEFT),
                'month' => $m,
                'year' => 2024,
                'start_date' => $start,
                'end_date' => $end,
                'status' => $m <= 6 ? 'closed' : 'open',
            ]);
        }

        // Run items for each staff × 6 closed months = up to 900 items
        // But we want ~150, so do first 2 months for all staff (300 items) or sample
        $closedPeriods = array_slice($periods, 0, 2);
        $itemCount = 0;

        foreach ($closedPeriods as $period) {
            foreach ($staff as $s) {
                if ($itemCount >= 180) {
                    break 2;
                }
                $salary = $s->basic_salary ?: G::randomAmount(2500, 7000);

                PayrollRunItem::create([
                    'period_id' => $period->id,
                    'employee_id' => $s->id,
                    'salary' => $salary,
                    'wage_type' => 'monthly',
                    'total_hours' => 208,
                    'hourly_rate_applied' => $salary / 208,
                    'period_start' => $period->start_date,
                    'period_end' => $period->end_date,
                    'epf_employer' => round($salary * 0.13, 2),
                    'epf_employee' => round($salary * 0.11, 2),
                    'epf_schedule_code' => 'A',
                    'socso_employer' => round(min($salary * 0.0175, 87.35), 2),
                    'socso_employee' => round(min($salary * 0.005, 49.75), 2),
                    'eis_employer' => round(min($salary * 0.002, 7.40), 2),
                    'eis_employee' => round(min($salary * 0.002, 7.40), 2),
                    'paid' => $period->month <= 3,
                    'paid_at' => $period->month <= 3 ? date('Y-m-d', strtotime($period->end_date.' +5 days')).' 10:00:00' : null,
                    'confirmed' => true,
                    'confirmed_at' => date('Y-m-d', strtotime($period->end_date.' +3 days')).' 14:00:00',
                    'confirmed_by' => 1,
                ]);
                $itemCount++;
            }
        }

        // Add adjustments for ~60 items
        $items = PayrollRunItem::all();
        $adjCount = 0;
        foreach ($items as $item) {
            if ($adjCount >= 150) {
                break;
            }
            if (rand(1, 3) === 1) {
                PayrollAdjustment::create([
                    'payroll_run_item_id' => $item->id,
                    'type' => 'earnings',
                    'label' => 'Overtime Allowance',
                    'amount' => G::randomAmount(50, 500),
                    'created_by' => 1,
                ]);
                $adjCount++;
            }
            if (rand(1, 5) === 1) {
                PayrollAdjustment::create([
                    'payroll_run_item_id' => $item->id,
                    'type' => 'deductions',
                    'label' => 'Late Deduction',
                    'amount' => G::randomAmount(10, 100),
                    'created_by' => 1,
                ]);
                $adjCount++;
            }
        }
    }
}
