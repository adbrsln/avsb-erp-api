<?php

namespace Database\Seeders;

use App\Models\ActivityLog;

class BulkActivitySeeder
{
    public function run(): void
    {
        $subjects = [
            'App\Models\Project', 'App\Models\Phase', 'App\Models\Task',
            'App\Models\Invoice', 'App\Models\Quotation', 'App\Models\Contract',
            'App\Models\StaffProfile', 'App\Models\Client', 'App\Models\Vendor',
            'App\Models\PurchaseOrder', 'App\Models\Bill', 'App\Models\Asset',
            'App\Models\ExpenseClaim', 'App\Models\LeaveApplication',
            'App\Models\Subcontractor', 'App\Models\SelfBilledInvoice',
        ];

        $events = ['created', 'updated', 'deleted', 'created', 'updated', 'updated', 'created'];
        $descriptions = [
            'Created new record', 'Updated record details', 'Deleted record',
            'Status changed', 'Field updated', 'Submitted for approval',
            'Approved', 'Rejected', 'Payment processed', 'Document uploaded',
        ];

        $batch = [];
        for ($i = 0; $i < 200; $i++) {
            $subjectType = $subjects[array_rand($subjects)];
            $ts = date('Y-m-d H:i:s', strtotime('2024-01-01 +'.rand(0, 364).' days '.rand(8, 18).':'.rand(0, 59).':00'));

            $batch[] = [
                'log_name' => 'default',
                'description' => $descriptions[array_rand($descriptions)],
                'subject_type' => $subjectType,
                'subject_id' => rand(1, 100),
                'causer_type' => 'App\Models\User',
                'causer_id' => rand(1, 5),
                'properties' => json_encode(['old' => [], 'attributes' => ['status' => 'active']]),
                'event' => $events[array_rand($events)],
                'created_at' => $ts,
                'updated_at' => $ts,
            ];
        }

        foreach (array_chunk($batch, 100) as $chunk) {
            ActivityLog::insert($chunk);
        }
    }
}
