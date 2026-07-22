<?php

namespace App\Seeds;

use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPrefSeeder
{
    public function run(): void
    {
        if (NotificationPreference::count() > 0) {
            return;
        }

        $eventTypes = [
            'LEAVE_APPLIED', 'LEAVE_APPROVED', 'LEAVE_REJECTED', 'LEAVE_CANCELLED',
            'CLAIM_SUBMITTED', 'CLAIM_APPROVED', 'CLAIM_REJECTED', 'CLAIM_PAID',
            'EXPENSE_CLAIM_SUBMITTED',
            'TIMECARD_SUBMITTED', 'TIMECARD_APPROVED', 'TIMECARD_REJECTED',
            'PROJECT_CREATED', 'PROJECT_COMPLETED', 'PROJECT_PAUSED', 'PROJECT_ASSIGNED',
            'project.created', 'project.assigned',
            'PHASE_COMPLETED', 'TASK_ASSIGNED', 'TASK_COMPLETED',
            'INVOICE_ISSUED', 'INVOICE_PAID', 'INVOICE_OVERDUE',
            'SUBCONTRACTOR_CLAIM_SUBMITTED', 'SUBCONTRACTOR_CLAIM_APPROVED',
            'SUBCONTRACTOR_CLAIM_VERIFIED', 'SUBCONTRACTOR_CLAIM_PAID',
            'PROJECT_CLAIM_SUBMITTED', 'PROJECT_CLAIM_APPROVED',
            'SELF_BILLED_ISSUED', 'SELF_BILLED_APPROVED',
            'PO_CREATED', 'BILL_RECEIVED',
            'PAYROLL_PROCESSED', 'PAYSLIP_READY',
            'ATTENDANCE_FLAGGED',
            'EINVOICE_SUBMITTED', 'EINVOICE_REJECTED',
            'LOW_STOCK_ALERT',
            'CONTRACT_EXPIRING', 'LICENSE_EXPIRING',
        ];

        $users = User::all();
        $batch = [];

        foreach ($users as $user) {
            foreach ($eventTypes as $event) {
                $batch[] = [
                    'user_id' => $user->id,
                    'event_type' => $event,
                    'email' => true,
                    'push' => true,
                    'in_app' => true,
                ];
            }
        }

        foreach (array_chunk($batch, 200) as $chunk) {
            NotificationPreference::insert($chunk);
        }
    }
}
