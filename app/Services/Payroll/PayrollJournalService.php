<?php

namespace App\Services\Payroll;

use App\Models\ChartOfAccount;
use App\Models\CompanySetting;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PayrollRunItem;
use Illuminate\Support\Facades\DB;

class PayrollJournalService
{
    private const REQUIRED_ACCOUNTS = ['6101', '6102', '6103', '6104', '2102', '2103', '2104', '2105', '2106'];

    public function post(PayrollRunItem $item): void
    {
        DB::transaction(function () use ($item) {
            $this->remove($item);

            $accounts = $this->accounts();
            $now = now();

            $entry = JournalEntry::create([
                'entry_number' => 'PAY-'.$item->id,
                'entry_date' => $now->toDateString(),
                'description' => 'Payroll item #'.$item->id,
                'reference_type' => 'payroll',
                'reference_id' => $item->id,
                'status' => 'posted',
                'created_by' => auth()->id() ?? null,
                'posted_at' => $now,
            ]);

            foreach ($this->lines($item, $accounts) as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }
        });
    }

    public function remove(PayrollRunItem $item): void
    {
        JournalEntry::where('reference_type', 'payroll')
            ->where('reference_id', $item->id)
            ->delete();
    }

    private function lines(PayrollRunItem $item, array $accounts): array
    {
        if ($item->wage_type === 'hourly_timesheet') {
            return [
                ['account_id' => $accounts['6101'], 'debit' => $item->salary, 'description' => 'Part-time gross pay'],
                ['account_id' => $accounts['bank'], 'credit' => $item->salary, 'description' => 'Part-time net pay'],
            ];
        }

        $lines = [
            ['account_id' => $accounts['6101'], 'debit' => $item->salary, 'description' => 'Gross salary'],
            ['account_id' => $accounts['6102'], 'debit' => $item->epf_employer, 'description' => 'Employer EPF'],
            ['account_id' => $accounts['6103'], 'debit' => $item->socso_employer, 'description' => 'Employer SOCSO'],
            ['account_id' => $accounts['6104'], 'debit' => $item->eis_employer, 'description' => 'Employer EIS'],
            ['account_id' => $accounts['2103'], 'credit' => $item->epf_employee + $item->epf_employer, 'description' => 'EPF payable (employee + employer)'],
            ['account_id' => $accounts['2104'], 'credit' => $item->socso_employee + $item->socso_employer + $item->socso_24h_employee, 'description' => 'SOCSO payable (employee + employer + 24h)'],
            ['account_id' => $accounts['2105'], 'credit' => $item->eis_employee + $item->eis_employer, 'description' => 'EIS payable (employee + employer)'],
            ['account_id' => $accounts['2106'], 'credit' => $item->pcb_employee, 'description' => 'PCB payable'],
            ['account_id' => $accounts['bank'], 'credit' => $item->net_pay, 'description' => 'Net pay'],
        ];

        // Employer-borne PCB: no employee deduction (net_pay excludes it) so
        // the employer pays it as an extra salary expense (DR 6101).
        if ($item->pcbBorne() && (float) $item->pcb_employee > 0) {
            $lines[] = ['account_id' => $accounts['6101'], 'debit' => $item->pcb_employee, 'description' => 'PCB borne by employer'];
        }

        if ((float) $item->zakat > 0) {
            $lines[] = ['account_id' => $accounts['2102'], 'credit' => $item->zakat, 'description' => 'Zakat payable'];
        }

        return $lines;
    }

    private function accounts(): array
    {
        $bankCode = (string) (CompanySetting::value('payroll_bank_account') ?: '1102');

        $codes = array_merge(self::REQUIRED_ACCOUNTS, [$bankCode]);
        $rows = ChartOfAccount::whereIn('code', $codes)->get();

        // Key by the RAW code (ChartOfAccount casts `code` to int — keyBy would give int keys).
        $map = [];
        foreach ($rows as $row) {
            $map[$row->getRawOriginal('code')] = $row->id;
        }

        $missing = array_values(array_diff($codes, array_keys($map)));
        if (! empty($missing)) {
            throw new \RuntimeException('Missing chart of accounts: '.implode(', ', $missing));
        }

        $map['bank'] = $map[$bankCode];

        return $map;
    }
}
