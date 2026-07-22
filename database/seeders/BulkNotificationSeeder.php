<?php

namespace Database\Seeders;

use App\Helpers\MalaysianDataGenerator as G;
use App\Models\NotificationQueue;
use App\Models\UserNotification;

class BulkNotificationSeeder
{
    public function run(): void
    {
        $eventTypes = [
            'LEAVE_APPLIED', 'LEAVE_APPROVED', 'LEAVE_REJECTED',
            'CLAIM_SUBMITTED', 'CLAIM_APPROVED', 'CLAIM_PAID',
            'INVOICE_ISSUED', 'INVOICE_PAID', 'INVOICE_OVERDUE',
            'PROJECT_CREATED', 'PROJECT_COMPLETED', 'PHASE_COMPLETED',
            'TASK_ASSIGNED', 'TASK_COMPLETED',
            'PAYROLL_PROCESSED', 'PAYSLIP_READY',
            'EINVOICE_SUBMITTED', 'LOW_STOCK_ALERT',
            'SUBCONTRACTOR_CLAIM_SUBMITTED', 'ATTENDANCE_FLAGGED',
        ];

        // ~150 notification queue entries
        $queueBatch = [];
        for ($i = 0; $i < 150; $i++) {
            $event = $eventTypes[array_rand($eventTypes)];
            $name = G::randomName();
            $isPending = rand(0, 1);

            $entry = [
                'event_type' => $event,
                'recipient_email' => G::randomEmail($name),
                'recipient_name' => $name,
                'subject' => 'Notification: '.str_replace('_', ' ', $event),
                'body' => 'This is a notification regarding '.str_replace('_', ' ', strtolower($event)).'.',
                'status' => $isPending ? 'pending' : 'sent',
                'scheduled_at' => $isPending ? date('Y-m-d H:i:s', time() + rand(3600, 86400)) : null,
                'sent_at' => $isPending ? null : G::randomDate('2024-01-01', '2024-12-31').' 10:00:00',
            ];
            $queueBatch[] = $entry;
        }
        foreach (array_chunk($queueBatch, 100) as $chunk) {
            NotificationQueue::insert($chunk);
        }

        // ~150 user notifications
        $userBatch = [];
        for ($i = 0; $i < 150; $i++) {
            $event = $eventTypes[array_rand($eventTypes)];
            $userBatch[] = [
                'user_id' => rand(1, 5),
                'title' => str_replace('_', ' ', $event),
                'body' => 'Notification body for '.str_replace('_', ' ', strtolower($event)),
                'url' => '/'.strtolower(str_replace('_', '/', $event)),
                'event_type' => $event,
                'is_read' => rand(0, 1),
                'created_at' => G::randomDate('2024-01-01', '2024-12-31').' 10:00:00',
            ];
        }
        foreach (array_chunk($userBatch, 100) as $chunk) {
            UserNotification::insert($chunk);
        }
    }
}
