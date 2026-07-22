<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;

class AccountingSeeder
{
    public function run(): void
    {
        if (FiscalPeriod::count() > 0) {
            return;
        }

        $coa = ChartOfAccount::all();
        if ($coa->isEmpty()) {
            return;
        }

        $admin = User::first();
        if (! $admin) {
            return;
        }

        $cashAcct = $coa->where('code', '1102')->first();       // Maybank Current Account
        $receivableAcct = $coa->where('code', '1104')->first(); // Trade Receivables
        $revenueAcct = $coa->where('code', '4101')->first();    // Project Revenue
        $sstPayableAcct = $coa->where('code', '2107')->first(); // SST Payable
        $expenseAcct = $coa->where('code', '6203')->first();    // Office Supplies

        if (! $cashAcct || ! $receivableAcct || ! $revenueAcct) {
            // Use any asset, liability, revenue, expense accounts
            $cashAcct = $coa->where('type', 'asset')->first();
            $receivableAcct = $coa->where('type', 'asset')->skip(1)->first();
            $revenueAcct = $coa->where('type', 'revenue')->first();
            $expenseAcct = $coa->where('type', 'expense')->first();
        }

        // Fiscal periods (monthly 2024)
        $fiscalPeriods = [];
        for ($m = 1; $m <= 6; $m++) {
            $start = '2024-'.str_pad($m, 2, '0', STR_PAD_LEFT).'-01';
            $end = date('Y-m-t', strtotime($start));
            $fiscalPeriods[] = FiscalPeriod::create([
                'name' => date('F Y', strtotime($start)),
                'start_date' => $start,
                'end_date' => $end,
                'type' => 'month',
                'status' => $m <= 4 ? 'closed' : 'open',
            ]);
        }

        // Journal entries for closed periods
        $entries = [
            ['date' => '2024-01-31', 'desc' => 'Jan 2024 revenue recognition', 'lines' => [
                ['account' => $receivableAcct, 'debit' => 50000, 'credit' => 0],
                ['account' => $revenueAcct, 'debit' => 0, 'credit' => 46296.30],
                ['account' => $sstPayableAcct, 'debit' => 0, 'credit' => 3703.70],
            ]],
            ['date' => '2024-01-31', 'desc' => 'Jan 2024 operating expenses', 'lines' => [
                ['account' => $expenseAcct, 'debit' => 28000, 'credit' => 0],
                ['account' => $cashAcct, 'debit' => 0, 'credit' => 28000],
            ]],
            ['date' => '2024-02-29', 'desc' => 'Feb 2024 revenue recognition', 'lines' => [
                ['account' => $receivableAcct, 'debit' => 65000, 'credit' => 0],
                ['account' => $revenueAcct, 'debit' => 0, 'credit' => 60185.19],
                ['account' => $sstPayableAcct, 'debit' => 0, 'credit' => 4814.81],
            ]],
            ['date' => '2024-02-29', 'desc' => 'Feb 2024 operating expenses', 'lines' => [
                ['account' => $expenseAcct, 'debit' => 32000, 'credit' => 0],
                ['account' => $cashAcct, 'debit' => 0, 'credit' => 32000],
            ]],
            ['date' => '2024-02-29', 'desc' => 'Equipment purchase - Wacker plate compactor', 'lines' => [
                ['account' => $coa->where('code', '1203')->first() ?? $coa->where('type', 'asset')->skip(2)->first(), 'debit' => 45000, 'credit' => 0],
                ['account' => $cashAcct, 'debit' => 0, 'credit' => 45000],
            ]],
            ['date' => '2024-03-31', 'desc' => 'Mar 2024 revenue recognition', 'lines' => [
                ['account' => $receivableAcct, 'debit' => 78000, 'credit' => 0],
                ['account' => $revenueAcct, 'debit' => 0, 'credit' => 72222.22],
                ['account' => $sstPayableAcct, 'debit' => 0, 'credit' => 5777.78],
            ]],
            ['date' => '2024-03-31', 'desc' => 'Mar 2024 operating expenses', 'lines' => [
                ['account' => $expenseAcct, 'debit' => 35000, 'credit' => 0],
                ['account' => $cashAcct, 'debit' => 0, 'credit' => 35000],
            ]],
            ['date' => '2024-03-31', 'desc' => 'Payroll run Mar 2024', 'lines' => [
                ['account' => $expenseAcct, 'debit' => 45000, 'credit' => 0],
                ['account' => $cashAcct, 'debit' => 0, 'credit' => 38000],
                ['account' => $coa->where('code', '2108')->first() ?? $coa->where('type', 'liability')->first(), 'debit' => 0, 'credit' => 7000],
            ]],
            ['date' => '2024-04-30', 'desc' => 'Apr 2024 revenue recognition', 'lines' => [
                ['account' => $receivableAcct, 'debit' => 45000, 'credit' => 0],
                ['account' => $revenueAcct, 'debit' => 0, 'credit' => 41666.67],
                ['account' => $sstPayableAcct, 'debit' => 0, 'credit' => 3333.33],
            ]],
            ['date' => '2024-04-30', 'desc' => 'Apr 2024 operating expenses', 'lines' => [
                ['account' => $expenseAcct, 'debit' => 25000, 'credit' => 0],
                ['account' => $cashAcct, 'debit' => 0, 'credit' => 25000],
            ]],
            ['date' => '2024-05-10', 'desc' => 'Client payment received - TNB', 'lines' => [
                ['account' => $cashAcct, 'debit' => 50000, 'credit' => 0],
                ['account' => $receivableAcct, 'debit' => 0, 'credit' => 50000],
            ]],
            ['date' => '2024-05-15', 'desc' => 'SST payment to LHDN', 'lines' => [
                ['account' => $sstPayableAcct, 'debit' => 3703.70, 'credit' => 0],
                ['account' => $cashAcct, 'debit' => 0, 'credit' => 3703.70],
            ]],
            ['date' => '2024-05-20', 'desc' => 'Material purchase - Asphalt', 'lines' => [
                ['account' => $coa->where('code', '1106')->first() ?? $cashAcct, 'debit' => 120000, 'credit' => 0],
                ['account' => $cashAcct, 'debit' => 0, 'credit' => 120000],
            ]],
            ['date' => '2024-06-05', 'desc' => 'Office rental June 2024', 'lines' => [
                ['account' => $expenseAcct, 'debit' => 8000, 'credit' => 0],
                ['account' => $cashAcct, 'debit' => 0, 'credit' => 8000],
            ]],
            ['date' => '2024-06-10', 'desc' => 'Invoice payment received - DBKL', 'lines' => [
                ['account' => $cashAcct, 'debit' => 39552, 'credit' => 0],
                ['account' => $receivableAcct, 'debit' => 0, 'credit' => 39552],
            ]],
        ];

        foreach ($entries as $eIdx => $entry) {
            $je = JournalEntry::create([
                'entry_number' => 'JE-2024-'.str_pad($eIdx + 1, 4, '0', STR_PAD_LEFT),
                'entry_date' => $entry['date'],
                'description' => $entry['desc'],
                'reference_type' => null,
                'reference_id' => null,
                'status' => 'posted',
                'created_by' => $admin->id,
                'posted_at' => $entry['date'].' 17:00:00',
            ]);

            $lineBatch = [];
            foreach ($entry['lines'] as $line) {
                if ($line['account']) {
                    $lineBatch[] = [
                        'journal_entry_id' => $je->id,
                        'account_id' => $line['account']->id,
                        'debit' => $line['debit'],
                        'credit' => $line['credit'],
                        'description' => $entry['desc'],
                    ];
                }
            }
            if (! empty($lineBatch)) {
                JournalEntryLine::insert($lineBatch);
            }
        }
    }
}
