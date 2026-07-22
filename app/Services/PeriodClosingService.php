<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;

class PeriodClosingService
{
    public static function close(FiscalPeriod $period, int $createdBy): array
    {
        if ($period->status !== 'open') {
            throw new \RuntimeException('Only open periods can be closed');
        }

        $incomeAccounts = ChartOfAccount::where('type', 'income')->get();
        $expenseAccounts = ChartOfAccount::where('type', 'expense')->get();

        $totalIncome = 0.0;
        $totalExpenses = 0.0;
        $incomeLines = [];
        $expenseLines = [];

        foreach ($incomeAccounts as $acct) {
            $balance = self::accountBalanceInPeriod($acct->id, $period);
            if ($balance != 0) {
                $incomeLines[] = ['account_id' => $acct->id, 'debit' => max(0, $balance), 'credit' => max(0, -$balance)];
                $totalIncome += $balance;
            }
        }
        foreach ($expenseAccounts as $acct) {
            $balance = self::accountBalanceInPeriod($acct->id, $period);
            if ($balance != 0) {
                $expenseLines[] = ['account_id' => $acct->id, 'debit' => max(0, $balance), 'credit' => max(0, -$balance)];
                $totalExpenses += $balance;
            }
        }

        $netProfit = $totalIncome - $totalExpenses;

        $cypl = ChartOfAccount::where('code', '3103')->first();

        $entry = JournalEntry::create([
            'entry_number' => (new NumberingService)->generate('journal'),
            'entry_date' => $period->end_date,
            'description' => 'Closing entry for ' . $period->name,
            'reference_type' => 'closing_entry',
            'status' => 'posted',
            'posted_at' => now(),
            'created_by' => $createdBy,
        ]);

        foreach ($incomeLines as $line) {
            JournalEntryLine::create(array_merge($line, ['journal_entry_id' => $entry->id]));
        }
        foreach ($expenseLines as $line) {
            JournalEntryLine::create(array_merge($line, ['journal_entry_id' => $entry->id]));
        }

        if ($netProfit != 0) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $cypl->id,
                'debit' => $netProfit < 0 ? abs($netProfit) : 0,
                'credit' => $netProfit > 0 ? $netProfit : 0,
                'description' => 'Net profit for ' . $period->name,
            ]);
        }

        $period->update(['status' => 'closed', 'closed_at' => now()]);

        return ['entry_id' => $entry->id, 'net_profit' => $netProfit];
    }

    private static function accountBalanceInPeriod(int $accountId, FiscalPeriod $period): float
    {
        return (float) (JournalEntryLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) use ($period) {
                $q->where('status', 'posted')
                  ->whereBetween('entry_date', [$period->start_date->format('Y-m-d'), $period->end_date->format('Y-m-d')]);
            })
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
            ->first()->balance ?? 0);
    }
}
