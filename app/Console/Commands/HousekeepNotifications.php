<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HousekeepNotifications extends Command
{
    protected $signature = 'cron:housekeep-notifications';

    protected $description = 'Purge old/expired/orphaned notification queue items';

    public function handle(): int
    {
        try {
            $stats = [];

            // 1. Force-stale processing items stuck >24h
            $stale = DB::table('notification_queue')
                ->where('status', 'processing')
                ->where('processing_since', '<', now()->subHours(24))
                ->update([
                    'status' => 'failed',
                    'error' => 'Stuck in processing >24h — force-failed by housekeep',
                    'processing_since' => null,
                ]);
            $stats[] = "{$stale} stale processing force-failed";

            // 2. Reset stale pending items older than 7 days
            $expired = DB::table('notification_queue')
                ->where('status', 'pending')
                ->where('created_at', '<', now()->subDays(7))
                ->update([
                    'status' => 'failed',
                    'error' => 'Expired — max queue age exceeded',
                ]);
            $stats[] = "{$expired} stale pending expired";

            // 3. Remove orphaned items
            $orphaned = 0;
            $orphanChecks = [
                ['model' => 'App\\Models\\LeaveApplication', 'table' => 'leave_applications'],
                ['model' => 'App\\Models\\ExpenseClaim', 'table' => 'claims'],
                ['model' => 'App\\Models\\ProjectClaim', 'table' => 'project_claims'],
                ['model' => 'App\\Models\\SubcontractorClaim', 'table' => 'subcontractor_claims'],
                ['model' => 'App\\Models\\SelfBilledInvoice', 'table' => 'self_billed_invoices'],
                ['model' => 'App\\Models\\Timecard', 'table' => 'timecards'],
                ['model' => 'App\\Models\\PurchaseOrder', 'table' => 'purchase_orders'],
                ['model' => 'App\\Models\\Invoice', 'table' => 'invoices'],
            ];
            foreach ($orphanChecks as $check) {
                $ids = DB::table('notification_queue')
                    ->where('model_type', $check['model'])
                    ->whereNotExists(function ($q) use ($check) {
                        $q->select(DB::raw(1))
                            ->from($check['table'])
                            ->whereColumn('id', 'notification_queue.model_id');
                    })
                    ->pluck('id');

                if ($ids->isNotEmpty()) {
                    $deleted = DB::table('notification_queue')->whereIn('id', $ids)->delete();
                    $orphaned += $deleted;
                }
            }
            $stats[] = "{$orphaned} orphaned items removed";

            // 4. Archive sent items older than 90 days, then purge
            $cutoffSent = now()->subDays(90);
            $toArchive = DB::table('notification_queue')
                ->where('status', 'sent')
                ->where('sent_at', '<', $cutoffSent)
                ->get();

            $archived = 0;
            foreach ($toArchive as $item) {
                DB::table('notification_logs')->insert([
                    'queue_id' => $item->id,
                    'event_type' => $item->event_type,
                    'recipient_email' => $item->recipient_email,
                    'recipient_name' => $item->recipient_name,
                    'subject' => $item->subject,
                    'body' => $item->body,
                    'status' => 'sent',
                    'sent_at' => $item->sent_at,
                ]);
                $archived++;
            }

            $purgedSent = DB::table('notification_queue')
                ->where('status', 'sent')
                ->where('sent_at', '<', $cutoffSent)
                ->delete();
            $stats[] = "{$purgedSent} sent items purged ({$archived} archived)";

            // 5. Purge old failed items (>30 days)
            $purgedFailed = DB::table('notification_queue')
                ->where('status', 'failed')
                ->where('updated_at', '<', now()->subDays(30))
                ->delete();
            $stats[] = "{$purgedFailed} failed items purged";

            // 6. Rotate notification_logs (>1 year)
            $rotated = DB::table('notification_logs')
                ->where('sent_at', '<', now()->subYear())
                ->delete();
            $stats[] = "{$rotated} log records rotated";

            $this->info(implode(' | ', $stats));
            Log::info('Housekeep: '.implode(' | ', $stats));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            Log::error('Housekeep error: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
