<?php

namespace App\Services\Payroll;

use App\Models\Attendance;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use App\Models\StaffProfile;

class PayrollProcessor
{
    private EPFCalculator $epfCalculator;

    private SocsoCalculator $socsoCalculator;

    private EisCalculator $eisCalculator;

    private Socso24Calculator $socso24Calculator;

    public function __construct()
    {
        $this->epfCalculator = new EPFCalculator;
        $this->socsoCalculator = new SocsoCalculator;
        $this->eisCalculator = new EisCalculator;
        $this->socso24Calculator = new Socso24Calculator;
    }

    public function process(int $periodId, ?array $employeeIds = null): array
    {
        $period = PayrollPeriod::find($periodId);
        if (! $period) {
            throw new \RuntimeException("Payroll period #{$periodId} not found.");
        }

        $query = StaffProfile::where('is_active', true)
            ->whereDoesntHave('user.roles', fn ($q) => $q->where('role', 'super_admin'));
        if ($employeeIds !== null) {
            $query->whereIn('id', $employeeIds);
        }
        $employees = $query->get();

        if ($employees->isEmpty()) {
            throw new \RuntimeException('No active employees found.');
        }

        $items = [];
        foreach ($employees as $employee) {
            // Skip employees who already have a paid item (don't overwrite paid records)
            $isPaid = PayrollRunItem::where('period_id', $periodId)
                ->where('employee_id', $employee->id)
                ->where('paid', true)
                ->exists();

            if ($isPaid) {
                continue;
            }

            // Skip employees with flagged overtime attendance in this period
            $hasFlagged = Attendance::where('staff_id', $employee->id)
                ->whereDate('date', '>=', $period->start_date)
                ->whereDate('date', '<=', $period->end_date)
                ->where('flagged', true)
                ->exists();

            if ($hasFlagged) {
                continue;
            }

            $salary = (float) ($employee->basic_salary ?? 0);

            // Bonus/additional earnings count into EPF wages (EPF Act 1991)
            // but NOT into SOCSO/EIS — their contribution base is wages only.
            // Honor adjustments already attached to a previous run so that
            // reprocessing doesn't reset EPF.
            $existingItem = PayrollRunItem::where('period_id', $periodId)
                ->where('employee_id', $employee->id)
                ->first();
            $earningsTotal = $existingItem
                ? (float) PayrollAdjustment::where('payroll_run_item_id', $existingItem->id)
                    ->where('type', 'earnings')
                    ->sum('amount')
                : 0.0;
            $epfSalary = round($salary + $earningsTotal, 2);

            $epf = $employee->epf_contributing
                ? $this->epfCalculator->calculateRaw(
                    $epfSalary,
                    str_contains((string) $employee->nationality, 'Malaysian') ? 'citizen' : 'non_citizen',
                    (bool) $employee->has_pr,
                    (bool) $employee->epf_member_before_aug_1998,
                    (string) $employee->date_of_birth,
                )
                : new EPFResult((new ScheduleDeterminer)->determine($employee), $epfSalary, 0.0, 0.0);
            $socso = $employee->socso_contributing
                ? $this->socsoCalculator->calculate($salary)
                : new SocsoResult($salary, 0.0, 0.0);
            $eis = $employee->eis_contributing
                ? $this->eisCalculator->calculate($salary)
                : new EisResult($salary, 0.0, 0.0);
            $socso24Amount = 0;
            if ($employee->socso_contributing && $employee->socso_24h_enabled) {
                $category = $employee->socso_category ?? 'first';
                $socso24Amount = $this->socso24Calculator->calculate($salary, $category)['amount'];
            }

            $taxYear = $period->year ?? (int) date('Y');
            if ($employee->pcb_contributing) {
                $pcb = (new PcbCalculator)->calculate(
                    $employee,
                    $taxYear,
                    new PcbContext(
                        monthlyGross: $salary,
                        employeeEpf: $epf->employeeAmount,
                        ytdGross: $this->ytdSum($employee->id, $period, 'salary'),
                        ytdPcb: $this->ytdSum($employee->id, $period, 'pcb_employee'),
                        ytdEpf: $this->ytdSum($employee->id, $period, 'epf_employee'),
                        zakat: (float) ($employee->zakat_monthly ?? 0),
                        month: (int) ($period->month ?? (int) date('n')),
                    )
                );
                $pcbMethod = $pcb->breakdown;
            } else {
                $pcb = null;
                $pcbMethod = ['pcb_contributing' => false];
            }

            $pcbMethod['pcb_borne_by_employer'] = $employee->pcbBorneEffective($period->end_date?->toDateString());

            PayrollRunItem::updateOrCreate(
                ['period_id' => $periodId, 'employee_id' => $employee->id],
                [
                    'salary' => $salary,
                    'epf_employer' => $epf->employerAmount,
                    'epf_employee' => $epf->employeeAmount,
                    'epf_schedule_code' => $epf->scheduleCode,
                    'socso_employer' => $socso->employerAmount,
                    'socso_employee' => $socso->employeeAmount,
                    'eis_employer' => $eis->employerAmount,
                    'eis_employee' => $eis->employeeAmount,
                    'socso_24h_employee' => $socso24Amount,
                    'pcb_employee' => $pcb?->amount ?? 0,
                    'zakat' => $pcb?->zakat ?? 0,
                    'pcb_tax_year' => $pcb ? $taxYear : null,
                    'pcb_method' => $pcbMethod,
                ]
            );

            $item = PayrollRunItem::where('period_id', $periodId)
                ->where('employee_id', $employee->id)
                ->first();

            $items[] = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'employee_code' => $employee->employee_id,
                'salary' => $salary,
                'epf_employer' => $epf->employerAmount,
                'epf_employee' => $epf->employeeAmount,
                'epf_schedule_code' => $epf->scheduleCode,
                'socso_employer' => $socso->employerAmount,
                'socso_employee' => $socso->employeeAmount,
                'eis_employer' => $eis->employerAmount,
                'eis_employee' => $eis->employeeAmount,
                'socso_24h_employee' => $socso24Amount,
                'pcb_employee' => $pcb?->amount ?? 0,
                'zakat' => $pcb?->zakat ?? 0,
                'pcb_tax_year' => $pcb ? $taxYear : null,
            ];
        }

        return [
            'period_id' => $periodId,
            'period_code' => $period->code,
            'total_employees' => count($items),
            'items' => $items,
        ];
    }

    private function ytdSum(int $employeeId, PayrollPeriod $period, string $column): float
    {
        $month = (int) $period->month;
        if ($month <= 1) {
            return 0.0;
        }

        return (float) PayrollRunItem::where('employee_id', $employeeId)
            ->whereHas('period', fn ($q) => $q->where('year', $period->year)->where('month', '<', $month))
            ->sum($column);
    }

    public function processPartTime(int $periodId): array
    {
        $period = PayrollPeriod::find($periodId);
        if (! $period) {
            throw new \RuntimeException("Payroll period #{$periodId} not found.");
        }

        $employees = StaffProfile::where('is_active', true)
            ->where('worker_status', 'part_time')
            ->whereDoesntHave('user.roles', fn ($q) => $q->where('role', 'super_admin'))
            ->get();

        if ($employees->isEmpty()) {
            throw new \RuntimeException('No active part-time staff found.');
        }

        $items = [];
        foreach ($employees as $employee) {
            $records = Attendance::where('staff_id', $employee->id)
                ->whereDate('date', '>=', $period->start_date)
                ->whereDate('date', '<=', $period->end_date)
                ->whereNotNull('clock_out')
                ->whereNull('payroll_run_item_id')
                ->get();

            $totalHours = round($records->sum('total_hours'), 2);
            $rate = (float) ($employee->hourly_rate ?? 0);
            $grossPay = round($totalHours * $rate, 2);

            if ($totalHours <= 0) {
                continue;
            }

            $item = PayrollRunItem::updateOrCreate(
                ['period_id' => $periodId, 'employee_id' => $employee->id],
                [
                    'wage_type' => 'hourly_timesheet',
                    'salary' => $grossPay,
                    'total_hours' => $totalHours,
                    'hourly_rate_applied' => $rate,
                    'period_start' => $period->start_date,
                    'period_end' => $period->end_date,
                    'epf_employer' => 0,
                    'epf_employee' => 0,
                    'epf_schedule_code' => null,
                    'socso_employer' => 0,
                    'socso_employee' => 0,
                    'eis_employer' => 0,
                    'eis_employee' => 0,
                ]
            );

            Attendance::whereIn('id', $records->pluck('id'))
                ->update(['payroll_run_item_id' => $item->id]);

            $items[] = [
                'item_id' => $item->id,
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'employee_code' => $employee->employee_id,
                'wage_type' => 'hourly_timesheet',
                'total_hours' => $totalHours,
                'hourly_rate' => $rate,
                'salary' => $grossPay,
                'days_worked' => $records->count(),
            ];
        }

        if (empty($items)) {
            throw new \RuntimeException('No unpaid part-time hours found for this period.');
        }

        return [
            'period_id' => $periodId,
            'period_code' => $period->code,
            'total_employees' => count($items),
            'items' => $items,
        ];
    }
}
