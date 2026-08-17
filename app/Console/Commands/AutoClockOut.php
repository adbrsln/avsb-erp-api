<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoClockOut extends Command
{
    protected $signature = 'attendance:auto-clock-out
        {--dry-run : Preview closings without writing}
        {--grace= : Override grace minutes}';

    protected $description = 'Auto clock-out open attendance sessions past work_end_time + grace (MY time)';

    public function handle(): int
    {
        if (! CompanySetting::value('auto_clock_out_enabled')) {
            $this->info('Auto clock-out disabled (auto_clock_out_enabled = false). Skipping.');

            return Command::SUCCESS;
        }

        $grace = (int) ($this->option('grace') ?? CompanySetting::value('auto_clock_out_grace_minutes') ?? 60);
        $dryRun = (bool) $this->option('dry-run');

        $sessions = Attendance::query()
            ->whereNull('clock_out')
            ->whereHas('staff', fn ($q) => $q->where('worker_status', '!=', 'part_time'))
            ->with('staff')
            ->get();

        $closed = 0;
        $skipped = 0;

        foreach ($sessions as $session) {
            $staff = $session->staff;
            if (! $staff) {
                $skipped++;

                continue;
            }

            $end = $staff->work_end_time ?: CompanySetting::value('work_end_time');
            if (! $end) {
                $skipped++;

                continue;
            }

            $closeAt = $this->resolveCloseAt($session, $end, $grace);
            if (! $closeAt || now()->lte($closeAt)) {
                $skipped++;

                continue;
            }

            $hours = round($session->clock_in->diffInMinutes($closeAt, true) / 60, 2);

            $this->line(sprintf(
                '%s → %s (in %s, close %s, %.2f h)',
                $session->id,
                $staff->name,
                $session->clock_in->toDateTimeString(),
                $closeAt->toDateTimeString(),
                $hours
            ));

            if ($dryRun) {
                continue;
            }

            // Re-read with atomic guard: skip if a manual clock-out won the race
            $fresh = Attendance::whereKey($session->id)->whereNull('clock_out')->first();
            if (! $fresh) {
                $skipped++;

                continue;
            }

            $fresh->update([
                'clock_out' => $closeAt,
                'total_hours' => $hours,
                'clock_out_ip' => 'system',
                'auto_closed' => true,
                'auto_close_reason' => 'schedule_end',
                'auto_closed_at' => now(),
            ]);
            $closed++;

            try {
                NotificationService::queue(
                    NotificationEvent::ATTENDANCE_AUTO_CLOSED,
                    $staff->email,
                    $staff->name,
                    [
                        'date' => $fresh->date instanceof \DateTimeInterface ? $fresh->date->format('Y-m-d') : (string) $fresh->date,
                        'clock_in' => $fresh->clock_in->setTimezone('Asia/Kuala_Lumpur')->format('H:i'),
                        'clock_out' => $closeAt->setTimezone('Asia/Kuala_Lumpur')->format('H:i'),
                        'hours' => number_format($hours, 2),
                        'url' => '/attendance',
                    ],
                    'App\\Models\\Attendance',
                    $fresh->id
                );
            } catch (\Throwable $e) {
                logger()->error('Notification failed: attendance.auto-closed', ['attendance_id' => $fresh->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("Auto clock-out: {$closed} closed, {$skipped} skipped".($dryRun ? ' (dry-run)' : ''));

        return Command::SUCCESS;
    }

    private function resolveCloseAt(Attendance $session, string $end, int $grace): ?Carbon
    {
        $closeAt = Carbon::createFromFormat('H:i', $end, 'Asia/Kuala_Lumpur')
            ->addMinutes($grace)
            ->utc();

        // Overnight shift: end time lands before clock-in → close is next morning
        if ($closeAt->lte($session->clock_in)) {
            $closeAt->addDay();
        }

        return $closeAt;
    }
}
