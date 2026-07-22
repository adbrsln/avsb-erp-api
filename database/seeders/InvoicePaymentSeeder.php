<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoicePayment;

class InvoicePaymentSeeder
{
    public function run(): void
    {
        if (InvoicePayment::count() > 0) {
            return;
        }

        $coa = ChartOfAccount::all();
        $cashAcct = $coa->where('code', '1102')->first();       // Maybank Current Account
        $receivableAcct = $coa->where('code', '1104')->first(); // Trade Receivables

        $invoices = Invoice::all();
        if ($invoices->isEmpty()) {
            return;
        }

        foreach ($invoices as $inv) {
            // Invoice payment: Debit Cash, Credit AR
            InvoicePayment::create([
                'invoice_id' => $inv->id,
                'amount' => $inv->total,
                'payment_date' => date('Y-m-d', strtotime($inv->due_date.' -5 days')),
                'debit_account_id' => $cashAcct?->id ?? $coa->where('type', 'asset')->first()?->id,
                'credit_account_id' => $receivableAcct?->id ?? $coa->where('type', 'asset')->first()?->id,
                'payment_reference' => 'TT-'.str_replace(['INV-', '-'], '', $inv->invoice_number),
                'notes' => 'Payment via bank transfer',
            ]);

            $inv->update(['status' => 'paid', 'processed_at' => date('Y-m-d H:i:s')]);

            if ($inv->contract_id) {
                $contract = Contract::find($inv->contract_id);
                if ($contract && $contract->billing_milestones) {
                    $milestones = is_array($contract->billing_milestones) ? $contract->billing_milestones : (json_decode($contract->billing_milestones ?? '[]', true) ?: []);
                    $changed = false;
                    foreach ($milestones as $idx => $m) {
                        if (($m['status'] ?? '') === 'billed') {
                            $milestones[$idx]['status'] = 'paid';
                            $changed = true;
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
