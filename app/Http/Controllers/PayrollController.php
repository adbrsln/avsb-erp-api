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
use App\Services\Payroll\EPFCalculator;
use App\Services\Payroll\PayrollProcessor;
use App\Services\Payroll\Socso24Calculator;
use App\Services\Payroll\SocsoCalculator;
use App\Services\PayslipGenerator;
use App\Traits\PaginatedResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    use PaginatedResponse;

    public function listPeriods(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = PayrollPeriod::withCount('items')->orderByDesc('year')->orderByDesc('month');

        return $this->paginate($query, $params);
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
            ->select('payroll_run_items.*', 'staff_profiles.name as employee_name', 'staff_profiles.employee_id as employee_code')
            ->where('payroll_run_items.id', $itemId)
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
            'total_employer' => round($epf->employerAmount + $socso->employerAmount + $eis->employerAmount, 2),
            'total_employee' => round($epf->employeeAmount + $socso->employeeAmount + $eis->employeeAmount + $socso24['amount'], 2),
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

        $adjustedSalary = $item->salary + $earningsTotal;

        $epf = (new EPFCalculator)->calculateRaw(
            $adjustedSalary,
            str_contains((string) $employee->nationality, 'Malaysian') ? 'citizen' : 'non_citizen',
            (bool) $employee->has_pr,
            (bool) $employee->epf_member_before_aug_1998,
            (string) $employee->date_of_birth,
        );
        $socso = (new SocsoCalculator)->calculate($adjustedSalary);
        $eis = (new EisCalculator)->calculate($adjustedSalary);
        $socso24 = (new Socso24Calculator)->calculate($adjustedSalary);

        $item->update([
            'epf_schedule_code' => $epf->scheduleCode,
            'epf_employer' => $epf->employerAmount,
            'epf_employee' => $epf->employeeAmount,
            'socso_employer' => $socso->employerAmount,
            'socso_employee' => $socso->employeeAmount,
            'eis_employer' => $eis->employerAmount,
            'eis_employee' => $eis->employeeAmount,
            'socso_24h_employee' => $socso24['amount'],
        ]);
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
            ],
        ]);
    }

    public function downloadPayslip(Request $request, int $id, int $itemId): JsonResponse
    {
        $item = PayrollRunItem::with('period')->find($itemId);
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

        $storagePath = 'uploads/payslips/'.$item->period_id.'/'.$item->id.'.pdf';
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

        $params = $request->query();
        if (isset($params['presign']) && $params['presign'] === '1') {
            $url = $storage->getPresignedUrl($storagePath);
            if ($url) {
                return response()->json(['url' => $url, 'filename' => 'Payslip_'.$item->id.'.pdf']);
            }
        }

        $pdfContent = $storage->get($storagePath);
        $filename = 'Payslip_'.$item->id.'.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => strlen($pdfContent),
        ]);
    }
}
