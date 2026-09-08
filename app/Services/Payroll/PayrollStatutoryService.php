<?php

namespace App\Services\Payroll;

use App\Models\PayrollRunItem;
use App\Models\StaffProfile;

/**
 * Recomputes a payroll item's statutory deductions (EPF/SOCSO/EIS/SKBBK/PCB)
 * from the wage breakdown + year-to-date figures. Shared by the adjustment
 * flow and the reversal cascade so the two never drift.
 */
class PayrollStatutoryService
{
    public function recalculate(PayrollRunItem $item): void
    {
        if ($item->wage_type === 'hourly_timesheet') {
            return;
        }

        $employee = StaffProfile::find($item->employee_id);
        if (! $employee) {
            return;
        }

        $breakdown = WageBreakdown::resolve($employee, $item->period, $item);

        $epf = $employee->epf_contributing
            ? (new EPFCalculator)->calculateRaw(
                $breakdown->epfBase,
                str_contains((string) $employee->nationality, 'Malaysian') ? 'citizen' : 'non_citizen',
                (bool) $employee->has_pr,
                (bool) $employee->epf_member_before_aug_1998,
                (string) $employee->date_of_birth,
            )
            : new EPFResult((new ScheduleDeterminer)->determine($employee), $breakdown->epfBase, 0.0, 0.0);
        $socso = $employee->socso_contributing
            ? (new SocsoCalculator)->calculate($breakdown->wagesBase)
            : new SocsoResult($breakdown->wagesBase, 0.0, 0.0);
        $eis = $employee->eis_contributing
            ? (new EisCalculator)->calculate($breakdown->wagesBase)
            : new EisResult($breakdown->wagesBase, 0.0, 0.0);
        $socso24 = ($employee->socso_contributing && $employee->socso_24h_enabled)
            ? (new Socso24Calculator)->calculate($breakdown->wagesBase, $employee->socso_category ?? 'first')
            : ['amount' => 0];

        $taxYear = $item->period?->year ?? (int) date('Y');
        $month = (int) ($item->period?->month ?? (int) date('n'));

        // PCB: ordinary remuneration is the wages base; additional remuneration
        // (bonus, overtime, arrears) spikes in its declaring period.
        $epfOnWages = $employee->epf_contributing
            ? (new EPFCalculator)->calculateRaw(
                $breakdown->wagesBase,
                str_contains((string) $employee->nationality, 'Malaysian') ? 'citizen' : 'non_citizen',
                (bool) $employee->has_pr,
                (bool) $employee->epf_member_before_aug_1998,
                (string) $employee->date_of_birth,
            )
            : new EPFResult((new ScheduleDeterminer)->determine($employee), $breakdown->wagesBase, 0.0, 0.0);

        $pcbMethod = ['pcb_contributing' => false];
        $pcb = null;

        if ($employee->pcb_contributing) {
            // Cumulative additional remuneration known so far this year, and
            // the amount known before THIS period (for the Design A spike:
            // only the incremental bonus tax is spiked in the declaring period).
            $additionalPrior = $this->pcbYtdAdditional($item);
            $additionalCumulative = $additionalPrior + $breakdown->additionalRemuneration;

            $pcb = (new PcbCalculator)->calculateRaw(
                monthlyGross: $breakdown->wagesBase,
                employeeEpf: $epfOnWages->employeeAmount,
                taxYear: $taxYear,
                workerCategory: $employee->worker_category ?? 'pemastautin',
                maritalStatus: $employee->marital_status,
                spouseWorking: $employee->spouse_working,
                spouseDisabled: (bool) $employee->spouse_disabled,
                childrenTax: $employee->children_tax,
                abilityStatus: $employee->ability_status ?? 'normal',
                zakat: (float) ($employee->zakat_monthly ?? 0),
                ytdGross: $this->pcbYtdSum($item, 'salary'),
                ytdPcb: $this->pcbYtdSum($item, 'pcb_employee'),
                ytdEpf: $this->pcbYtdSum($item, 'epf_employee'),
                additionalRemuneration: $additionalCumulative,
                additionalRemunerationPrior: $additionalPrior,
                month: $month,
            );

            $pcbMethod = $pcb->breakdown;
            $pcbMethod['additional_remuneration'] = $breakdown->additionalRemuneration;
            $pcbMethod['additional_pcb'] = (float) ($pcb->breakdown['bonus_tax'] ?? 0);
        }

        $pcbTotal = $pcb?->amount ?? 0;
        $pcbMethod['pcb_borne_by_employer'] = $employee->pcbBorneEffective($item->period?->end_date?->toDateString());

        $item->update([
            'epf_schedule_code' => $epf->scheduleCode,
            'epf_employer' => $epf->employerAmount,
            'epf_employee' => $epf->employeeAmount,
            'socso_employer' => $socso->employerAmount,
            'socso_employee' => $socso->employeeAmount,
            'eis_employer' => $eis->employerAmount,
            'eis_employee' => $eis->employeeAmount,
            'socso_24h_employee' => $socso24['amount'],
            'pcb_employee' => $pcbTotal,
            'zakat' => $pcb?->zakat ?? 0,
            'pcb_tax_year' => $pcb ? $taxYear : null,
            'pcb_method' => $pcbMethod,
            'wage_breakdown' => $breakdown->toArray(),
        ]);
    }

    private function pcbYtdSum(PayrollRunItem $item, string $column): float
    {
        $month = (int) ($item->period?->month ?? 0);
        if ($month <= 1) {
            return 0.0;
        }

        return (float) PayrollRunItem::where('employee_id', $item->employee_id)
            ->whereHas('period', fn ($q) => $q->where('year', $item->period->year)->where('month', '<', $month))
            ->sum($column);
    }

    private function pcbYtdAdditional(PayrollRunItem $item): float
    {
        $month = (int) ($item->period?->month ?? 0);
        if ($month <= 1) {
            return 0.0;
        }

        $items = PayrollRunItem::where('employee_id', $item->employee_id)
            ->whereHas('period', fn ($q) => $q->where('year', $item->period->year)->where('month', '<', $month))
            ->get();

        return (float) $items->sum(fn ($i) => (float) ($i->wage_breakdown['additional_remuneration'] ?? 0));
    }
}
