<?php

namespace App\Console\Commands;

use App\Services\Payroll\PcbScheduleSyncService;
use Illuminate\Console\Command;

class SyncPcbSchedule extends Command
{
    protected $signature = 'pcb:sync-schedule
        {--year= : Tax year(s) to sync, comma-separated (default: all configured)}
        {--dry-run : Preview counts without writing}';

    protected $description = 'Sync PCB tax brackets + reliefs from config/pcb.php into the DB (idempotent upsert)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $yearOption = $this->option('year');

        $years = $yearOption
            ? array_map('intval', explode(',', (string) $yearOption))
            : array_keys(config('pcb.years', []));

        $service = new PcbScheduleSyncService;
        $totalBrackets = 0;
        $totalReliefs = 0;

        foreach ($years as $year) {
            try {
                $result = $service->sync((int) $year, $dryRun);
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());

                return Command::FAILURE;
            }

            $label = $dryRun ? 'would write' : 'synced';
            $this->info("Year {$result['year']}: {$result['brackets']} brackets, {$result['reliefs']} reliefs ({$label})");
            $totalBrackets += $result['brackets'];
            $totalReliefs += $result['reliefs'];
        }

        $prefix = $dryRun ? 'Would sync' : 'Synced';
        $this->info("{$prefix} {$totalBrackets} brackets, {$totalReliefs} reliefs across ".count($years).' year(s).');

        return Command::SUCCESS;
    }
}
