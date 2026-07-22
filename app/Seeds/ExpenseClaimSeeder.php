<?php

namespace App\Seeds;

use App\Models\ClaimItem;
use App\Models\ExpenseClaim;
use App\Models\StaffProfile;

class ExpenseClaimSeeder
{
    public function run(): void
    {
        if (ExpenseClaim::count() > 5) {
            return;
        }

        $staff = StaffProfile::all();
        if ($staff->isEmpty()) {
            return;
        }

        $claims = [
            ['staff_idx' => 1, 'title' => 'Travel to TNB Banting Site', 'description' => 'Round trip travel to TNB Banting project meeting', 'total' => 180.00],
            ['staff_idx' => 2, 'title' => 'Office Supplies Reimbursement', 'description' => 'Purchased stationery for office', 'total' => 215.50],
            ['staff_idx' => 3, 'title' => 'Site Safety Equipment', 'description' => 'Additional PPE for site staff', 'total' => 420.00],
            ['staff_idx' => 1, 'title' => 'Client Entertainment - TNB Lunch', 'description' => 'Business lunch with TNB project team', 'total' => 350.00],
            ['staff_idx' => 4, 'title' => 'Fuel for Site Visits', 'description' => 'Petrol for multiple site visits in May', 'total' => 280.00],
            ['staff_idx' => 2, 'title' => 'Training Course Fee', 'description' => 'Safety supervisor refresher course', 'total' => 550.00],
            ['staff_idx' => 0, 'title' => 'Mileage Claim June', 'description' => 'Personal vehicle usage for project site visits', 'total' => 195.00],
            ['staff_idx' => 3, 'title' => 'Accommodation - Sabah Site', 'description' => 'Hotel stay for 3 nights at site', 'total' => 680.00],
            ['staff_idx' => 1, 'title' => 'Toll & Parking Reimbursement', 'description' => 'Toll fees and parking for April', 'total' => 145.00],
            ['staff_idx' => 4, 'title' => 'Equipment Repair Cost', 'description' => 'Minor repair of plate compactor', 'total' => 320.00],
        ];

        $refNum = 5;
        $categories = ['mileage', 'accommodation', 'toll', 'parking', 'office_supplies', 'training', 'entertainment'];

        foreach ($claims as $c) {
            $s = $staff->values()->get($c['staff_idx'] % $staff->count());
            if (! $s) {
                continue;
            }

            $status = $refNum % 3 === 0 ? 'approved' : ($refNum % 3 === 1 ? 'submitted' : 'rejected');
            $approverId = $status !== 'submitted'
                ? $staff->where('id', '!=', $s->id)->first()?->id ?? 1
                : null;

            $expenseClaim = ExpenseClaim::create([
                'claim_ref' => 'CLM-2024-'.str_pad($refNum, 3, '0', STR_PAD_LEFT),
                'staff_id' => $s->id,
                'title' => $c['title'],
                'description' => $c['description'],
                'status' => $status,
                'total_amount' => $c['total'],
                'submitted_date' => date('Y-m-d', strtotime('2024-0'.rand(3, 6).'-15')),
                'approver_id' => $approverId,
                'approved_at' => $approverId && $status === 'approved'
                    ? date('Y-m-d', strtotime('2024-0'.rand(3, 7).'-20')).' 10:00:00'
                    : null,
                'rejection_reason' => $status === 'rejected' ? 'Insufficient supporting documents' : null,
            ]);

            // 1-2 claim items per claim
            $numItems = rand(1, 2);
            for ($i = 0; $i < $numItems; $i++) {
                ClaimItem::create([
                    'claim_id' => $expenseClaim->id,
                    'description' => $c['title'].' - Item '.($i + 1),
                    'category' => $categories[array_rand($categories)],
                    'amount' => round($c['total'] / $numItems, 2),
                ]);
            }

            $refNum++;
        }
    }
}
