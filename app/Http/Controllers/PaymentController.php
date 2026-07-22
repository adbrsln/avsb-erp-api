<?php

namespace App\Http\Controllers;

use App\Models\ExpenseClaim;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use App\Models\ProjectClaim;
use App\Models\StaffProfile;
use App\Models\SubcontractorClaim;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationService;
use App\Services\PayslipGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $userRoles = $user?->getRoleNames() ?? [$user?->role ?? 'staff'];
        $params = $request->query();
        $typeFilter = $params['type'] ?? null;

        $items = [];

        if ((! $typeFilter || $typeFilter === 'expense-claim') && array_intersect($userRoles, ['admin', 'hr', 'finance', 'super_admin'])) {
            $claims = ExpenseClaim::with('staff')
                ->where('status', 'approved')
                ->whereNull('paid_at')
                ->orderByDesc('approved_at')
                ->get()
                ->map(fn ($c) => [
                    'type' => 'expense-claim',
                    'id' => $c->id,
                    'title' => $c->title ?? '',
                    'subtitle' => $c->staff->name ?? '',
                    'amount' => (float) ($c->total_amount ?? 0),
                    'applicant' => $c->staff->name ?? '',
                    'approved_at' => $c->approved_at?->toDateTimeString() ?? $c->created_at?->toDateTimeString() ?? '',
                    'status' => 'approved',
                    'url' => '/my-claims/'.$c->id,
                    'payment_reference' => null,
                ]);
            $items = array_merge($items, $claims->toArray());
        }

        if ((! $typeFilter || $typeFilter === 'project-claim') && array_intersect($userRoles, ['admin', 'finance', 'super_admin'])) {
            $projectClaims = ProjectClaim::with('project:id,name', 'submittedBy:id,name')
                ->where('status', 'approved')
                ->whereNull('paid_at')
                ->orderByDesc('approved_at')
                ->get()
                ->map(fn ($pc) => [
                    'type' => 'project-claim',
                    'id' => $pc->id,
                    'title' => $pc->title ?? '',
                    'subtitle' => $pc->project->name ?? '',
                    'amount' => (float) ($pc->amount ?? 0),
                    'applicant' => $pc->submittedBy->name ?? '',
                    'approved_at' => $pc->approved_at?->toDateTimeString() ?? $pc->created_at?->toDateTimeString() ?? '',
                    'status' => 'approved',
                    'url' => '/projects/'.$pc->project_id,
                    'payment_reference' => null,
                ]);
            $items = array_merge($items, $projectClaims->toArray());
        }

        if ((! $typeFilter || $typeFilter === 'subcon-claim') && array_intersect($userRoles, ['admin', 'pm', 'finance', 'super_admin'])) {
            $subconClaims = SubcontractorClaim::with('projectSubcontractor.subcontractor:id,company_name', 'submitter:id,name')
                ->where('status', 'approved')
                ->whereNull('paid_at')
                ->orderByDesc('approved_at')
                ->get()
                ->map(fn ($sc) => [
                    'type' => 'subcon-claim',
                    'id' => $sc->id,
                    'title' => 'Claim - '.($sc->claim_number ?? ''),
                    'subtitle' => $sc->projectSubcontractor->subcontractor->company_name ?? '',
                    'amount' => (float) ($sc->net_payable ?? 0),
                    'applicant' => $sc->submitter->name ?? '',
                    'approved_at' => $sc->approved_at?->toDateTimeString() ?? $sc->created_at?->toDateTimeString() ?? '',
                    'status' => 'approved',
                    'url' => '/subcontractors',
                    'payment_reference' => null,
                ]);
            $items = array_merge($items, $subconClaims->toArray());
        }

        if ((! $typeFilter || $typeFilter === 'payroll') && array_intersect($userRoles, ['admin', 'hr', 'super_admin'])) {
            $payrollItems = PayrollRunItem::with('employee')
                ->where('confirmed', true)
                ->where('paid', false)
                ->orderByDesc('confirmed_at')
                ->get()
                ->map(fn ($pi) => [
                    'type' => 'payroll',
                    'id' => $pi->id,
                    'title' => ($pi->employee->name ?? 'Unknown').' — '.$pi->period_id,
                    'subtitle' => '',
                    'amount' => (float) ($pi->net_pay ?? 0),
                    'applicant' => $pi->employee->name ?? '',
                    'net_pay' => $pi->net_pay,
                    'salary' => (float) ($pi->salary ?? 0),
                    'approved_at' => $pi->confirmed_at?->toDateTimeString() ?? '',
                    'status' => 'confirmed',
                    'url' => '/payroll/'.$pi->period_id.'/items/'.$pi->id.'/edit',
                    'payment_reference' => null,
                ]);
            $items = array_merge($items, $payrollItems->toArray());
        }

        usort($items, fn ($a, $b) => strcmp($b['approved_at'], $a['approved_at']));

        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($params['per_page'] ?? 20)));
        $total = count($items);
        $offset = ($page - 1) * $perPage;
        $paginated = array_slice($items, $offset, $perPage);

        $summary = [];

        if (! $typeFilter || $typeFilter === 'expense-claim') {
            $expCount = ExpenseClaim::where('status', 'approved')->whereNull('paid_at')->count();
            $expTotal = ExpenseClaim::where('status', 'approved')->whereNull('paid_at')->sum('total_amount');
            $summary['expense-claim'] = ['count' => $expCount, 'total' => round((float) $expTotal, 2)];
        }

        if (! $typeFilter || $typeFilter === 'project-claim') {
            $projCount = ProjectClaim::where('status', 'approved')->whereNull('paid_at')->count();
            $projTotal = ProjectClaim::where('status', 'approved')->whereNull('paid_at')->sum('amount');
            $summary['project-claim'] = ['count' => $projCount, 'total' => round((float) $projTotal, 2)];
        }

        if (! $typeFilter || $typeFilter === 'subcon-claim') {
            $subCount = SubcontractorClaim::where('status', 'approved')->whereNull('paid_at')->count();
            $subTotal = SubcontractorClaim::where('status', 'approved')->whereNull('paid_at')->sum('net_payable');
            $summary['subcon-claim'] = ['count' => $subCount, 'total' => round((float) $subTotal, 2)];
        }

        if (! $typeFilter || $typeFilter === 'payroll') {
            $payCount = PayrollRunItem::where('confirmed', true)->where('paid', false)->count();
            $payTotal = PayrollRunItem::where('confirmed', true)->where('paid', false)->sum(DB::raw('salary + COALESCE(epf_employer,0) + COALESCE(socso_employer,0) + COALESCE(eis_employer,0)'));
            $summary['payroll'] = ['count' => $payCount, 'total' => round((float) $payTotal, 2)];
        }

        return response()->json([
            'data' => $paginated,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
            'summary' => $summary,
        ]);
    }

    public function markPaid(Request $request): JsonResponse
    {
        $body = $request->all();
        $type = $body['type'] ?? '';
        $id = (int) ($body['id'] ?? 0);
        $paymentRef = trim($body['payment_reference'] ?? '');

        if (! $type || ! $id) {
            return response()->json(['error' => 'type and id are required'], 422);
        }

        $user = $request->user();
        $email = $user->email ?? '';
        $staff = StaffProfile::where('email', $email)->first();
        $now = date('Y-m-d H:i:s');

        return match ($type) {
            'expense-claim' => $this->markExpenseClaimPaid($id, $paymentRef, $now),
            'project-claim' => $this->markProjectClaimPaid($id, $now),
            'subcon-claim' => $this->markSubconClaimPaid($id, $paymentRef, $now),
            'payroll' => $this->markPayrollItemPaid($id, $staff?->id, $paymentRef, $now),
            default => response()->json(['error' => 'Invalid payment type'], 422),
        };
    }

    private function markExpenseClaimPaid(int $id, string $paymentRef, string $now): JsonResponse
    {
        $claim = ExpenseClaim::find($id);
        if (! $claim) {
            return response()->json(['error' => 'Claim not found'], 404);
        }
        if ($claim->status !== 'approved') {
            return response()->json(['error' => 'Only approved claims can be marked paid'], 422);
        }

        $updateData = ['status' => 'paid', 'paid_at' => $now];
        if ($paymentRef) {
            $updateData['payment_reference'] = $paymentRef;
        }
        $claim->update($updateData);

        try {
            $staff = $claim->staff;
            if ($staff && $staff->email) {
                NotificationService::queue(
                    NotificationEvent::CLAIM_PAID,
                    $staff->email,
                    $staff->name ?? '',
                    [
                        'title' => $claim->title ?? '',
                        'amount' => number_format($claim->total_amount ?? 0, 2),
                        'url' => '/my-claims/'.$claim->id,
                    ],
                    'App\\Models\\ExpenseClaim',
                    $claim->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Notification failed: claim.paid', ['claim_id' => $claim->id, 'error' => $e->getMessage()]);
        }

        return response()->json(['message' => 'Expense claim marked as paid']);
    }

    private function markProjectClaimPaid(int $id, string $now): JsonResponse
    {
        $claim = ProjectClaim::find($id);
        if (! $claim) {
            return response()->json(['error' => 'Project claim not found'], 404);
        }
        if ($claim->status !== 'approved') {
            return response()->json(['error' => 'Only approved project claims can be marked paid'], 422);
        }

        $claim->update(['status' => 'paid', 'paid_at' => $now]);

        try {
            $submitter = StaffProfile::find($claim->submitted_by);
            if ($submitter && $submitter->email) {
                NotificationService::queue(
                    NotificationEvent::PROJECT_CLAIM_PAID,
                    $submitter->email,
                    $submitter->name ?? '',
                    [
                        'title' => $claim->title ?? '',
                        'amount' => number_format($claim->amount ?? 0, 2),
                        'url' => '/projects/'.$claim->project_id,
                    ],
                    'App\\Models\\ProjectClaim',
                    $claim->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Notification failed: project-claim.paid', ['claim_id' => $claim->id, 'error' => $e->getMessage()]);
        }

        return response()->json(['message' => 'Project claim marked as paid']);
    }

    private function markSubconClaimPaid(int $id, string $paymentRef, string $now): JsonResponse
    {
        $claim = SubcontractorClaim::find($id);
        if (! $claim) {
            return response()->json(['error' => 'Subcontractor claim not found'], 404);
        }
        if ($claim->status !== 'approved') {
            return response()->json(['error' => 'Only approved subcontractor claims can be marked paid'], 422);
        }

        DB::transaction(function () use ($claim, $paymentRef, $now) {
            $claim->update([
                'status' => 'paid',
                'paid_at' => $now,
                'payment_reference' => $paymentRef ?: $claim->payment_reference,
            ]);

            $assignment = $claim->projectSubcontractor;
            if ($assignment) {
                $assignment->increment('retention_amount', $claim->retention_deducted ?? 0);
            }
        });

        try {
            $submitter = $claim->submitter;
            if ($submitter && $submitter->email) {
                NotificationService::queue(
                    NotificationEvent::SUBCON_CLAIM_PAID,
                    $submitter->email,
                    $submitter->name ?? '',
                    [
                        'claim_number' => $claim->claim_number ?? '',
                        'amount' => number_format($claim->net_payable ?? 0, 2),
                        'url' => '/subcontractors',
                    ],
                    'App\\Models\\SubcontractorClaim',
                    $claim->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Notification failed: subcon-claim.paid', ['claim_id' => $claim->id, 'error' => $e->getMessage()]);
        }

        return response()->json(['message' => 'Subcontractor claim marked as paid']);
    }

    private function markPayrollItemPaid(int $id, ?int $staffId, string $paymentRef, string $now): JsonResponse
    {
        $item = PayrollRunItem::find($id);
        if (! $item) {
            return response()->json(['error' => 'Payroll item not found'], 404);
        }
        if ($item->paid) {
            return response()->json(['error' => 'Item is already paid'], 422);
        }
        if (! $item->confirmed) {
            return response()->json(['error' => 'Item must be confirmed before marking as paid'], 422);
        }

        $updateData = ['paid' => true, 'paid_at' => $now];
        if ($staffId) {
            $updateData['paid_by'] = $staffId;
        }
        $item->update($updateData);

        try {
            (new PayslipGenerator)->generate($item->id);
        } catch (\Throwable $e) {
            Log::error('Payslip PDF generation failed', ['item_id' => $item->id, 'error' => $e->getMessage()]);
        }

        try {
            $employee = StaffProfile::find($item->employee_id);
            if ($employee && $employee->email) {
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
            Log::error('Notification failed: payslip.available', ['item_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json(['message' => 'Payroll item marked as paid']);
    }
}
