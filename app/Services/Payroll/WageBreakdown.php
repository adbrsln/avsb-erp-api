<?php

namespace App\Services\Payroll;

use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use App\Models\StaffProfile;

/**
 * Resolves a staff member's wage composition for a payroll period.
 *
 * Statutory treatment matrix (single source of truth):
 *   wages        → EPF + SOCSO/EIS + PCB ordinary (annualised)
 *   additional   → EPF only + PCB additional remuneration (one-shot)
 *   overtime     → PCB additional remuneration only (EPF-exempt)
 *   reimbursement→ not statutory (display only)
 */
class WageBreakdown
{
    public function __construct(
        public float $wagesBase,
        public float $epfBase,
        public float $additionalRemuneration,
        public float $reimbursementTotal,
        public array $allowances,
    ) {}

    public static function resolve(StaffProfile $employee, PayrollPeriod $period, ?PayrollRunItem $existingItem = null): self
    {
        $start = $period->start_date?->toDateString();
        $end = $period->end_date?->toDateString();

        $allowances = $employee->allowances()
            ->where(function ($q) use ($end) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $end);
            })
            ->where(function ($q) use ($start) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $start);
            })
            ->get();

        $byType = ['wages' => 0.0, 'additional' => 0.0, 'overtime' => 0.0, 'reimbursement' => 0.0];

        foreach ($allowances as $allowance) {
            $byType[$allowance->statutory_type] += (float) $allowance->amount;
        }

        if ($existingItem) {
            $adjustments = PayrollAdjustment::where('payroll_run_item_id', $existingItem->id)
                ->where('type', 'earnings')
                ->get();

            foreach ($adjustments as $adj) {
                $type = $adj->statutory_type ?? 'additional';
                if (isset($byType[$type])) {
                    $byType[$type] += (float) $adj->amount;
                }
            }
        }

        $wagesBase = round((float) ($employee->basic_salary ?? 0) + $byType['wages'], 2);

        return new self(
            wagesBase: $wagesBase,
            epfBase: round($wagesBase + $byType['additional'], 2),
            additionalRemuneration: round($byType['additional'] + $byType['overtime'], 2),
            reimbursementTotal: round($byType['reimbursement'], 2),
            allowances: $allowances->map(fn ($a) => [
                'name' => $a->name,
                'amount' => (float) $a->amount,
                'statutory_type' => $a->statutory_type,
            ])->values()->all(),
        );
    }

    public function toArray(): array
    {
        return [
            'wages_base' => $this->wagesBase,
            'epf_base' => $this->epfBase,
            'additional_remuneration' => $this->additionalRemuneration,
            'reimbursement' => $this->reimbursementTotal,
            'allowances' => $this->allowances,
        ];
    }
}
