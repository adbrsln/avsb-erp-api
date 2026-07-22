<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'cron:mark-overdue';

    protected $description = 'Mark unpaid/partially_paid invoices past due_date as overdue';

    public function handle(): int
    {
        $updated = DB::table('invoices')
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->where('due_date', '<', now()->toDateString())
            ->whereNotNull('due_date')
            ->update(['status' => 'overdue']);

        $this->info("Marked {$updated} invoice(s) as overdue.");

        return Command::SUCCESS;
    }
}
