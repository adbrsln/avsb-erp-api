<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use App\Models\StaffProfile;
use App\Services\FileStorageService;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationService;
use App\Services\Payroll\EisCalculator;
use App\Services\Payroll\EisResult;
use App\Services\Payroll\EPFCalculator;
use App\Services\Payroll\EPFResult;
use App\Services\Payroll\PayrollJournalService;
use App\Services\Payroll\PayrollProcessor;
use App\Services\Payroll\PcbCalculator;
use App\Services\Payroll\ScheduleDeterminer;
use App\Services\Payroll\Socso24Calculator;
use App\Services\Payroll\SocsoCalculator;
use App\Services\Payroll\SocsoResult;
use App\Services\PayslipGenerator;
use App\Traits\PaginatedResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PayrollController extends Controller
{
    use PaginatedResponse;

    public function listPeriods(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = PayrollPeriod::withCount('items');

        if (! empty($params['search'])) {
            $query->where('code', 'like', '%'.$params['search'].'%');
        }
        if (! empty($params['status']) && in_array($params['status'], ['open', 'closed'], true)) {
            $query->where('status', $params['status']);
        }
        if (! empty($params['year'])) {
            $query->where('year', (int) $params['year']);
        }

        $query->orderByDesc('year')->orderByDesc('month');

        $years = PayrollPeriod::query()
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return $this->paginate($query, $params, ['years' => $years]);
    }

    public function closePeriod(Request $request, int $id): JsonResponse
    {
        $period = PayrollPeriod::find($id);
        if (! $period) {
            return response()->json(['error' => 'Payroll period not found'], 404);
        }
        if ($period->status === 'closed') {
            return response()->json(['error' => 'Period is already closed'], 422);
        }

        // Closing locks the period: unconfirmed items could never be confirmed
        // or paid afterwards. Block the close until every payslip is confirmed.
        $unconfirmed = PayrollRunItem::where('period_id', $period->id)
            ->where('confirmed', false)
            ->count();
        if ($unconfirmed > 0) {
            return response()->json([
                'error' => "Cannot close: {$unconfirmed} payslip(s) not confirmed. Confirm or remove them first.",
            ], 422);
        }

        $period->update(['status' => 'closed']);

        return response()->json($period->fresh()->toArray());
    }

    public function reopenPeriod(Request $request, int $id): JsonResponse
    {
        $period = PayrollPeriod::find($id);
        if (! $period) {
            return response()->json(['error' => 'Payroll period not found'], 404);
        }
        if ($period->status === 'open') {
            return response()->json(['error' => 'Period is already open'], 422);
        }
        $period->update(['status' => 'open']);

        return response()->json($period->fresh()->toArray());
    }

    public function createPeriod(Request $request): JsonResponse
    {
        $body = $request->all();

        if (isset($body['month']) && isset($body['year'])) {
            $month = (int) $body['month'];
            $year = (int) $body['year'];
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));
            $code = date('F Y', strtotime($startDate));

            $exists = PayrollPeriod::where('month', $month)->where('year', $year)->exists();
            if ($exists) {
                return response()->json(['error' => 'A period for this month/year already exists'], 422);
            }

            $period = PayrollPeriod::create([
                'code' => $code,
                'month' => $month,
                'year' => $year,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            return response()->json($period->toArray(), 201);
        }

        $code = $body['code'] ?? '';
        $startDate = $body['start_date'] ?? '';
        $endDate = $body['end_date'] ?? '';

        if (! $code || ! $startDate || ! $endDate) {
            return response()->json(['error' => 'Provide month+year or code+start_date+end_date'], 400);
        }

        try {
            $period = PayrollPeriod::create([
                'code' => $code,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'month' => (int) date('n', strtotime($startDate)),
                'year' => (int) date('Y', strtotime($startDate)),
            ]);

            return response()->json($period->toArray(), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function processPeriod(Request $request, int $id): JsonResponse
    {
        $periodId = $id;
        $period = PayrollPeriod::find($periodId);
        if (! $period) {
            return response()->json(['error' => 'Payroll period not found'], 404);
        }
        if ($period->status === 'closed') {
            return response()->json(['error' => 'Cannot process a closed payroll period'], 422);
        }
        $body = $request->all();
        $employeeIds = $body['employee_ids'] ?? null;

        try {
            $result = (new PayrollProcessor)->process($periodId, $employeeIds);

            return response()->json($result);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function processPartTime(Request $request, int $id): JsonResponse
    {
        $periodId = $id;
        $period = PayrollPeriod::find($periodId);
        if (! $period) {
            return response()->json(['error' => 'Payroll period not found'], 404);
        }
        if ($period->status === 'closed') {
            return response()->json(['error' => 'Cannot process a closed payroll period'], 422);
        }

        try {
            $result = (new PayrollProcessor)->processPartTime($periodId);

            return response()->json($result);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function getPeriodItem(Request $request, int $id, int $itemId): JsonResponse
    {
        $item = PayrollRunItem::with('adjustments', 'period')
            ->join('staff_profiles', 'payroll_run_items.employee_id', '=', 'staff_profiles.id')
            ->select(
                'payroll_run_items.*',
                'staff_profiles.name as employee_name',
                'staff_profiles.employee_id as employee_code',
                'staff_profiles.identification_no',
                'staff_profiles.department',
                'staff_profiles.job_title',
                'staff_profiles.epf_no',
                'staff_profiles.socso_no',
                'staff_profiles.socso_no as eis_no',
                'staff_profiles.tax_no',
                'staff_profiles.bank_name',
                'staff_profiles.bank_account_no',
            )->where('payroll_run_items.id', $itemId)
            ->first();

        if (! $item) {
            return response()->json(['error' => 'Payroll item not found'], 404);
        }

        return response()->json($item);
    }

    public function getPeriodItems(Request $request, int $id): JsonResponse
    {
        $period = PayrollPeriod::find($id);
        if (! $period) {
            return response()->json(['error' => 'Payroll period not found'], 404);
        }

        $items = PayrollRunItem::with('adjustments')
            ->where('period_id', $period->id)
            ->join('staff_profiles', 'payroll_run_items.employee_id', '=', 'staff_profiles.id')
            ->select('payroll_run_items.*', 'staff_profiles.name as employee_name', 'staff_profiles.employee_id as employee_code')
            ->orderBy('staff_profiles.name')
            ->get()
            ->toArray();

        return response()->json(['data' => $items]);
    }

    public function exportItems(Request $request, int $id): Response|JsonResponse
    {
        $period = PayrollPeriod::find($id);
        if (! $period) {
            return response()->json(['error' => 'Payroll period not found'], 404);
        }

        $format = $request->query('format', 'csv');
        if (! in_array($format, ['csv', 'json'], true)) {
            $format = 'csv';
        }

        $items = PayrollRunItem::where('period_id', $period->id)
            ->join('staff_profiles', 'payroll_run_items.employee_id', '=', 'staff_profiles.id')
            ->select(
                'payroll_run_items.*',
                'staff_profiles.name as employee_name',
                'staff_profiles.employee_id as employee_code',
                'staff_profiles.department as employee_department',
                'staff_profiles.job_title as employee_job_title',
            )
            ->orderBy('staff_profiles.name')
            ->get();

        $rows = $items->map(fn ($item) => [
            'employee' => $item->employee_name,
            'employee_id' => $item->employee_code,
            'department' => $item->employee_department ?? '',
            'job_title' => $item->employee_job_title ?? '',
            'epf_schedule' => $item->epf_schedule_code ?? '',
            'gross_salary' => round((float) $item->salary, 2),
            'epf_employer' => round((float) $item->epf_employer, 2),
            'epf_employee' => round((float) $item->epf_employee, 2),
            'socso_employer' => round((float) $item->socso_employer, 2),
            'socso_employee' => round((float) $item->socso_employee, 2),
            'eis_employer' => round((float) $item->eis_employer, 2),
            'eis_employee' => round((float) $item->eis_employee, 2),
            'socso_24h' => round((float) ($item->socso_24h_employee ?? 0), 2),
            'pcb' => round((float) ($item->pcb_employee ?? 0), 2),
            'zakat' => round((float) ($item->zakat ?? 0), 2),
            'net_pay' => round((float) $item->net_pay, 2),
            'status' => $item->paid ? 'Paid' : ($item->confirmed ? 'Confirmed' : 'Pending'),
            'tax_year' => $item->pcb_tax_year ?? '',
        ])->values();

        $base = str_replace([' ', '/', '\\'], '-', $period->code);
        $filename = 'payroll-'.$base.'-'.date('Ymd');

        if ($format === 'json') {
            return response()->json([
                'period' => [
                    'id' => $period->id,
                    'code' => $period->code,
                    'start_date' => $period->start_date?->toDateString(),
                    'end_date' => $period->end_date?->toDateString(),
                    'status' => $period->status,
                ],
                'items' => $rows,
            ], 200, [
                'Content-Disposition' => 'attachment; filename="'.$filename.'.json"',
            ]);
        }

        $handle = fopen('php://temp', 'w+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, [
            'Employee', 'Employee ID', 'Department', 'Job Title', 'EPF Schedule',
            'Gross Salary', 'EPF Employer', 'EPF Employee', 'SOCSO Employer',
            'SOCSO Employee', 'EIS Employer', 'EIS Employee', 'SOCSO 24h',
            'PCB', 'Zakat', 'Net Pay', 'Status', 'Tax Year',
        ]);
        foreach ($rows as $row) {
            fputcsv($handle, array_values($row));
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
            'Content-Length' => strlen($csv),
        ]);
    }

    public function exportEpf(Request $request, int $id): Response|JsonResponse
    {
        $period = PayrollPeriod::find($id);
        if (! $period) {
            return response()->json(['error' => 'Payroll period not found'], 404);
        }

        $confirmedCount = PayrollRunItem::where('period_id', $period->id)->where('confirmed', true)->count();
        if ($confirmedCount === 0) {
            return response()->json(['error' => 'No confirmed payslips to export. Confirm the payslips first.'], 422);
        }

        $items = PayrollRunItem::where('period_id', $period->id)
            ->where('confirmed', true)
            ->join('staff_profiles', 'payroll_run_items.employee_id', '=', 'staff_profiles.id')
            ->select(
                'payroll_run_items.*',
                'staff_profiles.epf_no as staff_epf_no',
                'staff_profiles.identification_no as staff_ic',
                'staff_profiles.name as employee_name',
            )
            ->orderBy('staff_profiles.name')
            ->get();

        $handle = fopen('php://temp', 'w+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, [
            'Member EPF No', 'Employee Identification No', 'Employee Name', 'Employee Salary', 'Employer Amount', 'Employee Amount',
        ]);
        foreach ($items as $item) {
            if ((float) $item->epf_employer <= 0 && (float) $item->epf_employee <= 0) {
                continue; // no EPF contribution — not part of the EPF submission
            }
            fputcsv($handle, [
                $item->staff_epf_no ?? '',
                preg_replace('/[^0-9]/', '', (string) ($item->staff_ic ?? '')),
                $item->employee_name ?? '',
                number_format((float) $item->salary, 2, '.', ''),
                number_format((float) $item->epf_employer, 2, '.', ''),
                number_format((float) $item->epf_employee, 2, '.', ''),
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $base = str_replace([' ', '/', '\\'], '-', $period->code);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="epf-'.$base.'-'.date('Ymd').'.csv"',
            'Content-Length' => strlen($csv),
        ]);
    }

    public function exportSocso(Request $request, int $id): Response|JsonResponse
    {
        $period = PayrollPeriod::find($id);
        if (! $period) {
            return response()->json(['error' => 'Payroll period not found'], 404);
        }

        $confirmedCount = PayrollRunItem::where('period_id', $period->id)->where('confirmed', true)->count();
        if ($confirmedCount === 0) {
            return response()->json(['error' => 'No confirmed payslips to export. Confirm the payslips first.'], 422);
        }

        $company = CompanySetting::first();

        $items = PayrollRunItem::where('period_id', $period->id)
            ->where('confirmed', true)
            ->join('staff_profiles', 'payroll_run_items.employee_id', '=', 'staff_profiles.id')
            ->select(
                'payroll_run_items.*',
                'staff_profiles.identification_no as staff_ic',
                'staff_profiles.name as employee_name',
            )
            ->orderBy('staff_profiles.name')
            ->get();

        $month = sprintf('%02d%04d', (int) $period->month, (int) $period->year);

        $lines = [];
        foreach ($items as $item) {
            $hasSocso = (float) $item->socso_employer > 0
                || (float) $item->socso_employee > 0
                || (float) $item->eis_employer > 0
                || (float) $item->eis_employee > 0
                || (float) ($item->socso_24h_employee ?? 0) > 0;
            if (! $hasSocso) {
                continue; // no SOCSO/EIS/SKBBK contribution — not part of the submission
            }
            $lines[] = $this->socsoLine($item, $company, $month);
        }

        $content = implode("\r\n", $lines)."\r\n";
        $base = str_replace([' ', '/', '\\'], '-', $period->code);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="socso-'.$base.'-'.date('Ymd').'.txt"',
            'Content-Length' => strlen($content),
        ]);
    }

    public function exportPcb(Request $request, int $id): Response|JsonResponse
    {
        $period = PayrollPeriod::find($id);
        if (! $period) {
            return response()->json(['error' => 'Payroll period not found'], 404);
        }

        $confirmedCount = PayrollRunItem::where('period_id', $period->id)->where('confirmed', true)->count();
        if ($confirmedCount === 0) {
            return response()->json(['error' => 'No confirmed payslips to export. Confirm the payslips first.'], 422);
        }

        $company = CompanySetting::first();

        $items = PayrollRunItem::where('period_id', $period->id)
            ->where('confirmed', true)
            ->join('staff_profiles', 'payroll_run_items.employee_id', '=', 'staff_profiles.id')
            ->select(
                'payroll_run_items.*',
                'staff_profiles.tax_no as staff_tax_no',
                'staff_profiles.identification_no as staff_ic',
                'staff_profiles.name as employee_name',
                'staff_profiles.employee_id as employee_code',
            )
            ->orderBy('staff_profiles.name')
            ->get();

        $employerNumber = $this->cleanNumber($company?->tax_id_number ?? '', 10);

        $details = [];
        $totalMtd = 0;
        $mtdRecords = 0;
        foreach ($items as $item) {
            $mtdCents = (int) round((float) $item->pcb_employee * 100);
            if ($mtdCents <= 0) {
                continue; // zero-PCB employees are not part of the MTD submission
            }
            $details[] = $this->pcbDetailLine($item, $mtdCents);
            $totalMtd += $mtdCents;
            $mtdRecords++;
        }

        $month = sprintf('%02d', (int) $period->month);
        $year = sprintf('%04d', (int) $period->year);

        $header = 'H'
            .$employerNumber
            .$employerNumber
            .$year
            .$month
            .str_pad((string) $totalMtd, 10, '0', STR_PAD_LEFT)
            .str_pad((string) $mtdRecords, 5, '0', STR_PAD_LEFT)
            .str_pad('0', 10, '0', STR_PAD_LEFT)
            .str_pad('0', 5, '0', STR_PAD_LEFT);

        $content = $header."\r\n".implode("\r\n", $details)."\r\n";
        $filename = $employerNumber.$month.'_'.$year.'.txt';

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => strlen($content),
        ]);
    }

    private function pcbDetailLine(PayrollRunItem $item, int $mtdCents): string
    {
        $padRight = fn ($s, int $len) => str_pad((string) ($s ?? ''), $len, ' ', STR_PAD_RIGHT);

        return 'D'
            .$this->cleanNumber($item->staff_tax_no ?? '', 11)     // TIN
            .$padRight($item->employee_name ?? '', 60)             // Name
            .str_pad('', 12, ' ')                                  // Old IC
            .$padRight(str_replace('-', '', (string) ($item->staff_ic ?? '')), 12) // New IC
            .str_pad('', 12, ' ')                                  // Passport
            .str_pad('', 2, ' ')                                   // Country Code
            .str_pad((string) $mtdCents, 8, '0', STR_PAD_LEFT)     // MTD cents
            .str_pad('0', 8, '0', STR_PAD_LEFT)                    // CP38 cents
            .$padRight($item->employee_code ?? '', 10);            // Employee No.
    }

    private function cleanNumber(?string $value, int $length): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $value);

        return str_pad($digits, $length, '0', STR_PAD_LEFT);
    }

    private function socsoLine(PayrollRunItem $item, ?CompanySetting $company, string $month): string
    {
        $padRight = fn ($s, int $len) => str_pad((string) ($s ?? ''), $len, ' ', STR_PAD_RIGHT);
        $padCents = fn ($v, int $len) => str_pad((string) round((float) $v * 100), $len, '0', STR_PAD_LEFT);

        $line = '';
        $line .= $padRight($company?->socso_no ?? '', 12);      // Employer Code
        $line .= str_pad('', 20, ' ');                        // MyCoID / SSM (optional — blanked)
        $line .= $padRight(preg_replace('/[^0-9]/', '', (string) ($item->staff_ic ?? '')), 12);  // Identification No (IC)
        $line .= $padRight($item->employee_name ?? '', 150);    // Employee Name
        $line .= $month;                                        // Month MMYYYY
        $line .= $padCents($item->salary, 14);                  // Salary (cents)
        $line .= $padCents($item->socso_employer, 6);           // SOCSO employer (cents)
        $line .= $padCents($item->socso_employee, 6);           // SOCSO employee (cents)
        $line .= $padCents($item->eis_employer, 6);             // EIS employer (cents)
        $line .= $padCents($item->eis_employee, 6);             // EIS employee (cents)
        $line .= $padCents($item->socso_24h_employee ?? 0, 6);  // SKBBK employee (cents)
        $line .= str_pad('', 14, ' ');                          // Filler 1
        $line .= str_pad('', 20, ' ');                          // Filler 2

        return $line;
    }

    public function calculate(Request $request): JsonResponse
    {
        $body = $request->all();

        $salary = (float) ($body['salary'] ?? 0);
        $citizenship = $body['citizenship'] ?? 'citizen';
        $isPr = (bool) ($body['is_pr'] ?? false);
        $electedBefore1998 = (bool) ($body['elected_before_1998'] ?? false);
        $dateOfBirth = $body['date_of_birth'] ?? '2000-01-01';

        if ($salary <= 0) {
            return response()->json(['error' => 'salary must be greater than 0'], 400);
        }

        $epf = (new EPFCalculator)->calculateRaw($salary, $citizenship, $isPr, $electedBefore1998, $dateOfBirth);
        $socso = (new SocsoCalculator)->calculate($salary);
        $eis = (new EisCalculator)->calculate($salary);
        $socso24 = (new Socso24Calculator)->calculate($salary);
        $pcb = (new PcbCalculator)->calculateRaw(
            monthlyGross: $salary,
            employeeEpf: $epf->employeeAmount,
            taxYear: (int) date('Y'),
            workerCategory: 'pemastautin',
            maritalStatus: 'single',
            spouseWorking: null,
            spouseDisabled: false,
            childrenTax: null,
            abilityStatus: 'normal',
            month: 1,
        );

        return response()->json([
            'salary' => $salary,
            'epf_schedule_code' => $epf->scheduleCode,
            'epf_employer' => $epf->employerAmount,
            'epf_employee' => $epf->employeeAmount,
            'socso_employer' => $socso->employerAmount,
            'socso_employee' => $socso->employeeAmount,
            'eis_employer' => $eis->employerAmount,
            'eis_employee' => $eis->employeeAmount,
            'socso_24h_employee' => $socso24['amount'],
            'pcb' => $pcb->amount,
            'total_employer' => round($epf->employerAmount + $socso->employerAmount + $eis->employerAmount, 2),
            'total_employee' => round($epf->employeeAmount + $socso->employeeAmount + $eis->employeeAmount + $socso24['amount'] + $pcb->amount, 2),
        ]);
    }

    private function assertPeriodOpen(PayrollRunItem $item): bool
    {
        $period = PayrollPeriod::find($item->period_id);

        return $period && $period->status === 'open';
    }

    public function confirmItem(Request $request, int $id, int $itemId): JsonResponse
    {
        $item = PayrollRunItem::find($itemId);
        if (! $item) {
            return response()->json(['error' => 'Payroll item not found'], 404);
        }

        if (! $this->assertPeriodOpen($item)) {
            return response()->json(['error' => 'Payroll period is closed'], 422);
        }

        if ($item->paid) {
            return response()->json(['error' => 'Cannot confirm an already paid item'], 422);
        }

        if ($item->confirmed) {
            return response()->json(['error' => 'Item is already confirmed'], 422);
        }

        $user = $request->user();
        $email = $user->email ?? '';
        $staff = StaffProfile::where('email', $email)->first();

        $item->update([
            'confirmed' => true,
            'confirmed_at' => Carbon::now(),
            'confirmed_by' => $staff ? $staff->id : null,
        ]);

        return response()->json($item->fresh()->toArray());
    }

    public function markItemPaid(Request $request, int $id, int $itemId): JsonResponse
    {
        $item = PayrollRunItem::find($itemId);
        if (! $item) {
            return response()->json(['error' => 'Payroll item not found'], 404);
        }

        if (! $this->assertPeriodOpen($item)) {
            return response()->json(['error' => 'Payroll period is closed'], 422);
        }

        if ($item->paid) {
            return response()->json(['error' => 'Item is already paid'], 422);
        }

        if (! $item->confirmed) {
            return response()->json(['error' => 'Item must be confirmed before marking as paid'], 422);
        }

        $item->update(['paid' => true, 'paid_at' => Carbon::now()]);

        try {
            (new PayrollJournalService)->post($item);
        } catch (\Throwable $e) {
            logger()->error('Payroll journal entry failed', ['item_id' => $item->id, 'error' => $e->getMessage()]);
        }

        try {
            (new PayslipGenerator)->generate($item->id);
        } catch (\Exception $e) {
            logger()->error('Payslip PDF generation failed', ['item_id' => $item->id, 'error' => $e->getMessage()]);
        }

        try {
            $employee = StaffProfile::find($item->employee_id);
            if ($employee) {
                $period = PayrollPeriod::find($item->period_id);
                NotificationService::queue(
                    NotificationEvent::PAYSLIP_AVAILABLE,
                    $employee->email,
                    $employee->name,
                    [
                        'period' => $period?->code ?? '',
                        'net_pay' => number_format($item->net_pay ?? 0, 2),
                        'url' => '/my-payslips',
                    ],
                    'App\\Models\\PayrollRunItem',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: payslip.available', ['item_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item->toArray());
    }

    public function bulkMarkPaid(Request $request, int $id): JsonResponse
    {
        $period = PayrollPeriod::find($id);
        if (! $period) {
            return response()->json(['error' => 'Payroll period not found'], 404);
        }

        if ($period->status === 'closed') {
            return response()->json(['error' => 'Cannot mark paid in a closed payroll period'], 422);
        }

        $body = $request->all();
        $employeeIds = $body['employee_ids'] ?? [];

        if (empty($employeeIds)) {
            return response()->json(['error' => 'employee_ids is required'], 400);
        }

        $now = Carbon::now();
        $items = PayrollRunItem::where('period_id', $period->id)
            ->whereIn('employee_id', $employeeIds)
            ->get();

        $updated = 0;
        $skipped = 0;
        $updatedIds = [];
        foreach ($items as $item) {
            if ($item->paid) {
                $skipped++;

                continue;
            }
            if (! $item->confirmed) {
                $skipped++;

                continue;
            }
            $item->update(['paid' => true, 'paid_at' => $now]);
            try {
                (new PayrollJournalService)->post($item);
            } catch (\Throwable $e) {
                logger()->error('Payroll journal entry failed (bulk)', ['item_id' => $item->id, 'error' => $e->getMessage()]);
            }
            try {
                (new PayslipGenerator)->generate($item->id);
            } catch (\Exception $e) {
                logger()->error('Payslip PDF generation failed (bulk)', ['item_id' => $item->id, 'error' => $e->getMessage()]);
            }
            $updatedIds[] = $item->id;
            $updated++;
        }

        try {
            $period = PayrollPeriod::find($period->id);
            $notifyItems = PayrollRunItem::whereIn('id', $updatedIds)->get();
            foreach ($notifyItems as $pi) {
                $employee = StaffProfile::find($pi->employee_id);
                if ($employee) {
                    NotificationService::queue(
                        NotificationEvent::PAYSLIP_AVAILABLE,
                        $employee->email,
                        $employee->name,
                        [
                            'period' => $period?->code ?? '',
                            'net_pay' => number_format($pi->net_pay ?? 0, 2),
                            'url' => '/my-payslips',
                        ],
                        'App\\Models\\PayrollRunItem',
                        $pi->id
                    );
                }
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: payslip.available (bulk)', ['error' => $e->getMessage()]);
        }

        return response()->json(['updated' => $updated, 'skipped' => $skipped]);
    }

    private function recalculateStatutory(PayrollRunItem $item): void
    {
        if ($item->wage_type === 'hourly_timesheet') {
            return;
        }

        $employee = StaffProfile::find($item->employee_id);
        if (! $employee) {
            return;
        }

        $earningsTotal = PayrollAdjustment::where('payroll_run_item_id', $item->id)
            ->where('type', 'earnings')
            ->sum('amount');

        $baseSalary = (float) $item->salary;
        $adjustedSalary = $baseSalary + $earningsTotal;

        $epf = $employee->epf_contributing
            ? (new EPFCalculator)->calculateRaw(
                $adjustedSalary,
                str_contains((string) $employee->nationality, 'Malaysian') ? 'citizen' : 'non_citizen',
                (bool) $employee->has_pr,
                (bool) $employee->epf_member_before_aug_1998,
                (string) $employee->date_of_birth,
            )
            : new EPFResult((new ScheduleDeterminer)->determine($employee), $adjustedSalary, 0.0, 0.0);
        $socso = $employee->socso_contributing
            ? (new SocsoCalculator)->calculate($adjustedSalary)
            : new SocsoResult($adjustedSalary, 0.0, 0.0);
        $eis = $employee->eis_contributing
            ? (new EisCalculator)->calculate($adjustedSalary)
            : new EisResult($adjustedSalary, 0.0, 0.0);
        $socso24 = ($employee->socso_contributing && $employee->socso_24h_enabled)
            ? (new Socso24Calculator)->calculate($adjustedSalary, $employee->socso_category ?? 'first')
            : ['amount' => 0];

        $taxYear = $item->period?->year ?? (int) date('Y');
        $month = (int) ($item->period?->month ?? (int) date('n'));

        // PCB: ordinary salary is the base; earnings adjustments are treated
        // as LHDN additional remuneration (one-shot), not salary growth.
        $epfBase = $employee->epf_contributing
            ? (new EPFCalculator)->calculateRaw(
                $baseSalary,
                str_contains((string) $employee->nationality, 'Malaysian') ? 'citizen' : 'non_citizen',
                (bool) $employee->has_pr,
                (bool) $employee->epf_member_before_aug_1998,
                (string) $employee->date_of_birth,
            )
            : new EPFResult((new ScheduleDeterminer)->determine($employee), $baseSalary, 0.0, 0.0);

        $additionalPcb = 0.0;
        $pcbMethod = ['pcb_contributing' => false];
        $pcb = null;

        if ($employee->pcb_contributing) {
            $pcb = (new PcbCalculator)->calculateRaw(
                monthlyGross: $baseSalary,
                employeeEpf: $epfBase->employeeAmount,
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
                month: $month,
            );

            if ($earningsTotal > 0) {
                $additionalPcb = (new PcbCalculator)->additionalRemunerationPcb(
                    $pcb,
                    $earningsTotal,
                    max(0.0, $epf->employeeAmount - $epfBase->employeeAmount),
                );
            }

            $pcbMethod = $pcb->breakdown;
            $pcbMethod['additional_remuneration'] = $earningsTotal;
            $pcbMethod['additional_pcb'] = $additionalPcb;
        }

        $pcbTotal = ($pcb?->amount ?? 0) + $additionalPcb;
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

    public function getItemAdjustments(Request $request, int $id, int $itemId): JsonResponse
    {
        $item = PayrollRunItem::find($itemId);
        if (! $item) {
            return response()->json(['error' => 'Payroll item not found'], 404);
        }

        $adjustments = PayrollAdjustment::where('payroll_run_item_id', $item->id)->get()->toArray();

        return response()->json(['data' => $adjustments]);
    }

    public function createItemAdjustment(Request $request, int $id, int $itemId): JsonResponse
    {
        $item = PayrollRunItem::find($itemId);
        if (! $item) {
            return response()->json(['error' => 'Payroll item not found'], 404);
        }

        if (! $this->assertPeriodOpen($item)) {
            return response()->json(['error' => 'Payroll period is closed'], 422);
        }

        if ($item->paid) {
            return response()->json(['error' => 'Cannot adjust a paid item'], 422);
        }

        $body = $request->all();
        $type = $body['type'] ?? '';
        $label = $body['label'] ?? '';
        $amount = (float) ($body['amount'] ?? 0);

        if (! in_array($type, ['earnings', 'deductions'])) {
            return response()->json(['error' => 'type must be "earnings" or "deductions"'], 400);
        }

        if (! $label || $amount <= 0) {
            return response()->json(['error' => 'label and a positive amount are required'], 400);
        }

        $user = $request->user();
        $email = $user->email ?? '';
        $staff = StaffProfile::where('email', $email)->first();

        $adjustment = PayrollAdjustment::create([
            'payroll_run_item_id' => $item->id,
            'type' => $type,
            'label' => $label,
            'amount' => $amount,
            'created_by' => $staff ? $staff->id : null,
        ]);

        if ($type === 'earnings') {
            $this->recalculateStatutory($item);
        }

        return response()->json($adjustment->toArray(), 201);
    }

    public function deleteItemAdjustment(Request $request, int $id, int $itemId, int $adjustmentId): JsonResponse
    {
        $adjustment = PayrollAdjustment::find($adjustmentId);
        if (! $adjustment) {
            return response()->json(['error' => 'Adjustment not found'], 404);
        }

        $item = PayrollRunItem::find($adjustment->payroll_run_item_id);
        if (! $item) {
            return response()->json(['error' => 'Payroll item not found'], 404);
        }

        if (! $this->assertPeriodOpen($item)) {
            return response()->json(['error' => 'Payroll period is closed'], 422);
        }
        if ($item && $item->paid) {
            return response()->json(['error' => 'Cannot delete adjustment from a paid item'], 422);
        }

        $adjustmentType = $adjustment->type;
        $adjustmentItemId = $adjustment->payroll_run_item_id;
        $adjustment->delete();

        if ($adjustmentType === 'earnings' && $item) {
            $this->recalculateStatutory($item->fresh());
        }

        return response()->json(['message' => 'Adjustment deleted']);
    }

    public function myPayslips(Request $request): JsonResponse
    {
        $user = $request->user();
        $email = $user->email ?? '';

        $staff = StaffProfile::where('email', $email)->first();
        if (! $staff) {
            return response()->json(['data' => [], 'meta' => ['total' => 0], 'company' => null]);
        }

        $company = CompanySetting::first();

        $params = $request->query();

        $query = PayrollRunItem::with('adjustments')
            ->where('payroll_run_items.employee_id', $staff->id)
            ->where('payroll_run_items.paid', true)
            ->join('payroll_periods', 'payroll_run_items.period_id', '=', 'payroll_periods.id')
            ->select(
                'payroll_run_items.*',
                'payroll_periods.id as period_id',
                'payroll_periods.code as period_code',
                'payroll_periods.start_date as period_start',
                'payroll_periods.end_date as period_end'
            )
            ->orderByDesc('payroll_periods.start_date');

        return $this->paginate($query, $params, [
            'company' => $company ? $company->toArray() : null,
            'employee' => [
                'name' => $staff->name,
                'employee_id' => $staff->employee_id,
                'identification_no' => $staff->identification_no,
                'department' => $staff->department,
                'job_title' => $staff->job_title,
                'epf_no' => $staff->epf_no,
                'socso_no' => $staff->socso_no,
                'eis_no' => $staff->socso_no,
                'tax_no' => $staff->tax_no,
                'bank_name' => $staff->bank_name,
                'bank_account_no' => $staff->bank_account_no,
            ],
            'default_sort' => 'payroll_run_items.id',
            'sortable' => ['payroll_run_items.id', 'payroll_periods.start_date'],
        ]);
    }

    public function downloadPayslip(Request $request, int $periodId = 0, int $itemId = 0): Response|JsonResponse
    {
        // Support both: /payroll/payslips/{itemId} and /payroll/periods/{id}/items/{itemId}
        $actualItemId = $itemId ?: $periodId;
        $item = PayrollRunItem::with('period')->find($actualItemId);
        if (! $item) {
            return response()->json(['error' => 'Payslip not found'], 404);
        }

        if (! $item->paid) {
            return response()->json(['error' => 'Payslip not yet paid'], 403);
        }

        $user = $request->user();
        $email = $user->email ?? '';
        $staff = StaffProfile::where('email', $email)->first();

        if (! $staff || $staff->id !== $item->employee_id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $storagePath = 'payslips/'.$item->period_id.'/'.$item->id.'.pdf';
        $storage = new FileStorageService;

        if (! $storage->exists($storagePath)) {
            try {
                (new PayslipGenerator)->generate($item->id);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to generate payslip'], 500);
            }
        }

        if (! $storage->exists($storagePath)) {
            return response()->json(['error' => 'Payslip file not found'], 404);
        }

        $filename = 'Payslip_'.$item->id.'.pdf';
        $url = $storage->getPresignedUrl($storagePath, 5, $filename);
        if ($url) {
            return response()->json(['url' => $url, 'filename' => $filename]);
        }

        $pdfContent = $storage->get($storagePath);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => strlen($pdfContent),
        ]);
    }
}
