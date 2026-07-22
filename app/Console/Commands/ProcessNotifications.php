<?php

namespace App\Console\Commands;

use App\Services\Notification\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessNotifications extends Command
{
    protected $signature = 'cron:process-notifications {--limit=50 : Max items to process}';

    protected $description = 'Process pending notifications from the queue';

    public function handle(NotificationService $service): int
    {
        try {
            $limit = (int) $this->option('limit');
            $result = $service->sendFromQueue($limit);

            $total = ($result['sent'] ?? 0) + ($result['failed'] ?? 0);
            if ($total > 0) {
                $this->info("Processed {$total} notification(s) — sent: {$result['sent']}, failed: {$result['failed']}");
                Log::info("Notification queue: processed {$total} items", $result);
            } else {
                $this->info('No pending notifications.');
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            Log::error('Notification queue error: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
