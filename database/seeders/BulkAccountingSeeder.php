<?php

namespace Database\Seeders;

use App\Helpers\MalaysianDataGenerator as G;
use App\Models\ChartOfAccount;
use App\Models\Contract;
use App\Models\FiscalPeriod;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;

class BulkAccountingSeeder
{
    public function run(): void
    {
        $coa = ChartOfAccount::all();
        $admin = User::first();

        $cashAcct = $coa->where('code', '1102')->first();
        $receivableAcct = $coa->where('code', '1104')->first();
        $revenueAcct = $coa->where('code', '4101')->first();
        $sstPayableAcct = $coa->where('code', '2107')->first();
        $expenseAcct = $coa->where('code', '6203')->first();

        // Fiscal periods (monthly 2024)
        for ($m = 1; $m <= 12; $m++) {
            $start = '2024-'.str_pad($m, 2, '0', STR_PAD_LEFT).'-01';
            $end = date('Y-m-t', strtotime($start));
            FiscalPeriod::create([
                'name' => date('F Y', strtotime($start)),
                'start_date' => $start,
                'end_date' => $end,
                'type' => 'month',
                'status' => $m <= 6 ? 'closed' : 'open',
            ]);
        }

        // ~50 Journal Entries
        $jeDescriptions = [
            'Revenue recognition', 'Operating expenses', 'Payroll processing',
            'Material purchase', 'Equipment acquisition', 'Client payment received',
            'Vendor payment', 'SST payment', 'Depreciation entry', 'Accrual adjustment',
            'Prepayment amortization', 'Bank charges', 'Utility bills', 'Insurance premium',
            'Professional fees', 'Office rental', 'Maintenance expenses', 'Travel claims',
            'Project progress billing', 'Retention release',
        ];

        $allAccounts = $coa->toArray();
        $jeCount = 0;
        for ($m = 1; $m <= 6; $m++) {
            for ($j = 0; $j < 8 && $jeCount < 50; $j++) {
                $desc = $jeDescriptions[$j % count($jeDescriptions)];
                $entryDate = '2024-'.str_pad($m, 2, '0', STR_PAD_LEFT).'-'.str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);

                $je = JournalEntry::create([
                    'entry_number' => 'JE-BULK-'.str_pad($jeCount + 1, 4, '0', STR_PAD_LEFT),
                    'entry_date' => $entryDate,
                    'description' => $desc.' - '.date('F Y', strtotime($entryDate)),
                    'reference_type' => null,
                    'reference_id' => null,
                    'status' => 'posted',
                    'created_by' => $admin?->id ?? 1,
                    'posted_at' => $entryDate.' 17:00:00',
                ]);

                // 2-3 lines per JE
                $numLines = rand(2, 3);
                $totalDebit = 0;
                $lines = [];
                for ($l = 0; $l < $numLines; $l++) {
                    $acct = $allAccounts[array_rand($allAccounts)];
                    $amount = G::randomAmount(1000, 50000);
                    $isDebit = $l === 0 || ($l < $numLines - 1 && rand(0, 1));

                    $lines[] = [
                        'journal_entry_id' => $je->id,
                        'account_id' => $acct['id'],
                        'debit' => $isDebit ? $amount : 0,
                        'credit' => $isDebit ? 0 : $amount,
                        'description' => $desc,
                    ];
                    $totalDebit += $isDebit ? $amount : 0;
                }
                JournalEntryLine::insert($lines);
                $jeCount++;
            }
        }

        // Invoice payments for existing invoices (~50)
        $invoices = Invoice::all();
        foreach ($invoices as $inv) {
            if ($cashAcct && $receivableAcct) {
                InvoicePayment::create([
                    'invoice_id' => $inv->id,
                    'amount' => $inv->total,
                    'payment_date' => G::randomDate('2024-01-01', '2024-12-31'),
                    'debit_account_id' => $cashAcct->id,
                    'credit_account_id' => $receivableAcct->id,
                    'payment_reference' => 'TT-BULK-'.str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                    'notes' => 'Bulk generated payment',
                ]);

                $inv->update(['status' => 'paid', 'processed_at' => date('Y-m-d H:i:s')]);

                if ($inv->contract_id) {
                    $contract = Contract::find($inv->contract_id);
                    if ($contract && $contract->billing_milestones) {
                        $milestones = is_array($contract->billing_milestones) ? $contract->billing_milestones : (json_decode($contract->billing_milestones ?? '[]', true) ?: []);
                        $changed = false;
                        // First try marking billed milestones as paid
                        $foundBilled = false;
                        foreach ($milestones as $idx => $m) {
                            if (($m['status'] ?? '') === 'billed') {
                                $milestones[$idx]['status'] = 'paid';
                                $changed = true;
                                $foundBilled = true;
                            }
                        }
                        // If no billed milestones found, mark first pending as paid
                        if (! $foundBilled) {
                            foreach ($milestones as $idx => $m) {
                                if (($m['status'] ?? '') === 'pending') {
                                    $milestones[$idx]['status'] = 'paid';
                                    $changed = true;
                                    break;
                                }
                            }
                        }
                        if ($changed) {
                            $contract->billing_milestones = $milestones;
                            $contract->save();
                        }
                    }
                }
            }
        }
    }
}
