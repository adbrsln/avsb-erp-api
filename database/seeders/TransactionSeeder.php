<?php

namespace Database\Seeders;

use App\Models\ClaimItem;
use App\Models\ExpenseClaim;
use App\Models\LeaveApplication;
use App\Models\PayRun;
use App\Models\Timecard;

class TransactionSeeder
{
    public function run(): void
    {
        if (LeaveApplication::count() > 0) {
            return;
        }

        LeaveApplication::create([
            'leave_ref' => 'LV-2024-001', 'staff_id' => 2,
            'type' => 'annual', 'start_date' => '2024-03-01', 'end_date' => '2024-03-03',
            'reason' => 'Personal leave', 'status' => 'approved', 'approver_id' => 1,
            'approved_at' => '2024-02-20 09:00:00',
        ]);
        LeaveApplication::create([
            'leave_ref' => 'LV-2024-002', 'staff_id' => 3,
            'type' => 'medical', 'start_date' => '2024-02-15', 'end_date' => '2024-02-15',
            'reason' => 'MC', 'status' => 'pending',
        ]);
        LeaveApplication::create([
            'leave_ref' => 'LV-2024-003', 'staff_id' => 3,
            'type' => 'annual', 'start_date' => '2024-04-10', 'end_date' => '2024-04-12',
            'reason' => 'Family event', 'status' => 'approved', 'approver_id' => 1,
            'approved_at' => '2024-04-01 10:00:00',
        ]);
        LeaveApplication::create([
            'leave_ref' => 'LV-2024-004', 'staff_id' => 4,
            'type' => 'unpaid', 'start_date' => '2024-05-01', 'end_date' => '2024-05-02',
            'reason' => 'Personal matters', 'status' => 'rejected', 'approver_id' => 1,
        ]);
        LeaveApplication::create([
            'leave_ref' => 'LV-2024-005', 'staff_id' => 4,
            'type' => 'emergency', 'start_date' => '2024-06-05', 'end_date' => '2024-06-05',
            'reason' => 'Family emergency', 'status' => 'approved', 'approver_id' => 1,
            'approved_at' => '2024-06-03 08:00:00',
        ]);

        $c1 = ExpenseClaim::create([
            'claim_ref' => 'CLM-2024-001', 'staff_id' => 3,
            'title' => 'Fuel Reimbursement Feb 2024',
            'description' => 'Fuel for site visits', 'status' => 'approved',
            'total_amount' => 350.00, 'submitted_date' => '2024-02-28',
            'approver_id' => 1, 'approved_at' => '2024-03-01 10:00:00',
        ]);
        ClaimItem::insert([
            ['claim_id' => $c1->id, 'description' => 'Petrol - Week 1', 'category' => 'mileage', 'amount' => 100],
            ['claim_id' => $c1->id, 'description' => 'Petrol - Week 2', 'category' => 'mileage', 'amount' => 120],
            ['claim_id' => $c1->id, 'description' => 'Petrol - Week 3', 'category' => 'mileage', 'amount' => 130],
        ]);

        $c2 = ExpenseClaim::create([
            'claim_ref' => 'CLM-2024-002', 'staff_id' => 3,
            'title' => 'Tool Purchase', 'description' => 'Replacement safety gear',
            'status' => 'submitted', 'total_amount' => 250.00, 'submitted_date' => '2024-03-05',
        ]);
        ClaimItem::insert([
            ['claim_id' => $c2->id, 'description' => 'Safety helmet', 'category' => 'office_supplies', 'amount' => 80],
            ['claim_id' => $c2->id, 'description' => 'Safety boots', 'category' => 'office_supplies', 'amount' => 170],
        ]);

        $c3 = ExpenseClaim::create([
            'claim_ref' => 'CLM-2024-003', 'staff_id' => 3,
            'title' => 'Travel Expense April', 'description' => 'Travel to project site',
            'status' => 'approved', 'total_amount' => 520.00, 'submitted_date' => '2024-04-15',
            'approver_id' => 1, 'approved_at' => '2024-04-20 09:00:00',
        ]);
        ClaimItem::insert([
            ['claim_id' => $c3->id, 'description' => 'Toll fees', 'category' => 'toll', 'amount' => 120],
            ['claim_id' => $c3->id, 'description' => 'Parking fees', 'category' => 'parking', 'amount' => 50],
            ['claim_id' => $c3->id, 'description' => 'Accommodation', 'category' => 'accommodation', 'amount' => 350],
        ]);

        $c4 = ExpenseClaim::create([
            'claim_ref' => 'CLM-2024-004', 'staff_id' => 4,
            'title' => 'Materials for site repair', 'description' => 'Emergency material purchase',
            'status' => 'submitted', 'total_amount' => 890.00, 'submitted_date' => '2024-05-10',
        ]);
        ClaimItem::insert([
            ['claim_id' => $c4->id, 'description' => 'Cement bags', 'category' => 'office_supplies', 'amount' => 450],
            ['claim_id' => $c4->id, 'description' => 'Steel rebars', 'category' => 'office_supplies', 'amount' => 440],
        ]);

        Timecard::insert([
            ['staff_id' => 3, 'project_id' => 1, 'date' => '2024-02-01', 'hours_worked' => 8, 'description' => 'Milling setup', 'status' => 'approved'],
            ['staff_id' => 3, 'project_id' => 1, 'date' => '2024-02-02', 'hours_worked' => 9, 'description' => 'Milling operation', 'status' => 'approved'],
            ['staff_id' => 4, 'project_id' => 2, 'date' => '2024-02-01', 'hours_worked' => 10, 'description' => 'Milling machine prep', 'status' => 'approved'],
            ['staff_id' => 3, 'project_id' => 1, 'date' => '2024-03-10', 'hours_worked' => 8, 'description' => 'Paving groundwork', 'status' => 'pending'],
            ['staff_id' => 4, 'project_id' => 2, 'date' => '2024-03-11', 'hours_worked' => 7.5, 'description' => 'Site inspection', 'status' => 'pending'],
            ['staff_id' => 3, 'project_id' => 1, 'date' => '2024-03-12', 'hours_worked' => 9.5, 'description' => 'Asphalt laying', 'status' => 'rejected'],
        ]);

        PayRun::create([
            'staff_id' => 3, 'pay_run_number' => 'PR-2024-001',
            'period_start' => '2024-02-01', 'period_end' => '2024-02-29',
            'total_hours' => 168, 'hourly_rate' => 20.83, 'gross_pay' => 3500.00,
            'deductions' => 425.00, 'net_pay' => 3075.00, 'status' => 'paid',
            'paid_at' => '2024-03-05 10:00:00',
        ]);
        PayRun::create([
            'staff_id' => 2, 'pay_run_number' => 'PR-2024-002',
            'period_start' => '2024-03-01', 'period_end' => '2024-03-31',
            'total_hours' => 200, 'hourly_rate' => 32.50, 'gross_pay' => 6500.00,
            'deductions' => 500.00, 'net_pay' => 6000.00, 'status' => 'pending',
        ]);
        PayRun::create([
            'staff_id' => 4, 'pay_run_number' => 'PR-2024-003',
            'period_start' => '2024-03-01', 'period_end' => '2024-03-31',
            'total_hours' => 180, 'hourly_rate' => 25.00, 'gross_pay' => 4500.00,
            'deductions' => 200.00, 'net_pay' => 4300.00, 'status' => 'pending',
        ]);
    }
}
