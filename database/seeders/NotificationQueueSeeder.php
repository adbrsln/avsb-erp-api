<?php

namespace Database\Seeders;

use App\Models\NotificationQueue;

class NotificationQueueSeeder
{
    public function run(): void
    {
        if (NotificationQueue::count() > 0) {
            return;
        }

        $queueEntries = [
            ['event_type' => 'LEAVE_APPLIED', 'recipient_email' => 'ahmadi@example.com', 'recipient_name' => 'Ahmadi', 'subject' => 'Leave Application Submitted', 'body' => 'Your leave application has been submitted for approval.', 'status' => 'sent', 'sent_at' => '2024-03-01 09:00:00'],
            ['event_type' => 'LEAVE_APPROVED', 'recipient_email' => 'nadia@example.com', 'recipient_name' => 'Nadia', 'subject' => 'Leave Approved', 'body' => 'Your annual leave has been approved.', 'status' => 'sent', 'sent_at' => '2024-04-01 10:00:00'],
            ['event_type' => 'CLAIM_SUBMITTED', 'recipient_email' => 'staff@example.com', 'recipient_name' => 'Staff', 'subject' => 'Expense Claim Submitted', 'body' => 'Your expense claim has been submitted.', 'status' => 'sent', 'sent_at' => '2024-03-05 11:00:00'],
            ['event_type' => 'CLAIM_APPROVED', 'recipient_email' => 'nadia@example.com', 'recipient_name' => 'Nadia', 'subject' => 'Claim Approved', 'body' => 'Your expense claim has been approved.', 'status' => 'sent', 'sent_at' => '2024-03-20 14:00:00'],
            ['event_type' => 'INVOICE_ISSUED', 'recipient_email' => 'azamvsb@gmail.com', 'recipient_name' => 'Admin', 'subject' => 'Invoice INV-2024-001 Issued', 'body' => 'Invoice has been issued for project Jalan Tun Razak Resurfacing.', 'status' => 'sent', 'sent_at' => '2024-01-20 10:00:00'],
            ['event_type' => 'PROJECT_CREATED', 'recipient_email' => 'ahmadi@example.com', 'recipient_name' => 'Ahmadi', 'subject' => 'New Project Created', 'body' => 'A new project has been created.', 'status' => 'sent', 'sent_at' => '2024-01-15 08:00:00'],
            ['event_type' => 'TASK_ASSIGNED', 'recipient_email' => 'staff@example.com', 'recipient_name' => 'Staff', 'subject' => 'Task Assigned to You', 'body' => 'A new task has been assigned to you.', 'status' => 'sent', 'sent_at' => '2024-01-15 09:00:00'],
            ['event_type' => 'PHASE_COMPLETED', 'recipient_email' => 'ahmadi@example.com', 'recipient_name' => 'Ahmadi', 'subject' => 'Phase Completed', 'body' => 'A project phase has been marked as completed.', 'status' => 'sent', 'sent_at' => '2024-01-25 16:00:00'],
            ['event_type' => 'LOW_STOCK_ALERT', 'recipient_email' => 'azamvsb@gmail.com', 'recipient_name' => 'Admin', 'subject' => 'Low Stock Alert', 'body' => 'Glass Beads stock is below reorder level.', 'status' => 'pending', 'scheduled_at' => date('Y-m-d H:i:s', time() + 3600)],
            ['event_type' => 'PAYSLIP_READY', 'recipient_email' => 'staff@example.com', 'recipient_name' => 'Staff', 'subject' => 'Payslip Ready', 'body' => 'Your payslip for the period is now available.', 'status' => 'sent', 'sent_at' => '2024-04-05 10:00:00'],
            ['event_type' => 'EINVOICE_SUBMITTED', 'recipient_email' => 'azamvsb@gmail.com', 'recipient_name' => 'Admin', 'subject' => 'E-Invoice Submitted', 'body' => 'E-invoice has been submitted to LHDN.', 'status' => 'sent', 'sent_at' => '2024-05-01 12:00:00'],
            ['event_type' => 'BILL_RECEIVED', 'recipient_email' => 'nadia@example.com', 'recipient_name' => 'Nadia', 'subject' => 'New Bill Received', 'body' => 'A new vendor bill has been received.', 'status' => 'pending', 'scheduled_at' => date('Y-m-d H:i:s', time() + 7200)],
            ['event_type' => 'ATTENDANCE_FLAGGED', 'recipient_email' => 'ahmadi@example.com', 'recipient_name' => 'Ahmadi', 'subject' => 'Attendance Flagged', 'body' => 'An attendance record has been flagged for review.', 'status' => 'pending', 'scheduled_at' => date('Y-m-d H:i:s', time() + 1800)],
            ['event_type' => 'SUBCONTRACTOR_CLAIM_SUBMITTED', 'recipient_email' => 'azamvsb@gmail.com', 'recipient_name' => 'Admin', 'subject' => 'Subcontractor Claim Submitted', 'body' => 'A subcontractor claim has been submitted for approval.', 'status' => 'sent', 'sent_at' => '2024-04-20 15:00:00'],
            ['event_type' => 'PROJECT_COMPLETED', 'recipient_email' => 'azamvsb@gmail.com', 'recipient_name' => 'Admin', 'subject' => 'Project Completed', 'body' => 'A project has been marked as completed.', 'status' => 'pending', 'scheduled_at' => date('Y-m-d H:i:s', time() + 86400)],
        ];

        foreach ($queueEntries as $entry) {
            NotificationQueue::create($entry);
        }
    }
}
