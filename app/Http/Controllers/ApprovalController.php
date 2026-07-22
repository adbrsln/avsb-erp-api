<?php

namespace App\Http\Controllers;

use App\Models\ExpenseClaim;
use App\Models\LeaveApplication;
use App\Models\ProjectClaim;
use App\Models\SelfBilledInvoice;
use App\Models\StaffProfile;
use App\Models\SubcontractorClaim;
use App\Models\Timecard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $userRoles = $user->roles ?? [$user->role ?? 'staff'];
        $params = $request->all();
        $typeFilter = $params['type'] ?? null;

        $items = [];

        if ((! $typeFilter || $typeFilter === 'leave') && array_intersect($userRoles, ['admin', 'pm', 'super_admin'])) {
            $leaves = LeaveApplication::with('staff')
                ->where('status', 'pending')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($l) => [
                    'type' => 'leave',
                    'id' => $l->id,
                    'title' => ($l->staff->name ?? 'Unknown').' - '.$l->type,
                    'subtitle' => $l->days.' days ('.$l->start_date->format('d M').' - '.$l->end_date->format('d M').')',
                    'amount' => null,
                    'submitter' => $l->staff->name ?? '',
                    'submitted_at' => $l->created_at?->toDateTimeString() ?? $l->start_date?->format('Y-m-d') ?? '',
                    'status' => $l->status,
                    'allowed_actions' => ['approve', 'reject'],
                ]);
            $items = array_merge($items, $leaves->toArray());
        }

        if ((! $typeFilter || $typeFilter === 'claim') && array_intersect($userRoles, ['admin', 'hr', 'finance', 'super_admin'])) {
            $claims = ExpenseClaim::with('staff')
                ->whereIn('status', ['submitted', 'pending'])
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($c) => [
                    'type' => 'claim',
                    'id' => $c->id,
                    'title' => $c->title ?? '',
                    'subtitle' => $c->staff->name ?? '',
                    'amount' => (float) ($c->total_amount ?? 0),
                    'submitter' => $c->staff->name ?? '',
                    'submitted_at' => $c->created_at?->toDateTimeString() ?? '',
                    'status' => $c->status,
                    'allowed_actions' => $c->status === 'submitted' || $c->status === 'pending'
                        ? ['approve', 'reject']
                        : [],
                ]);
            $items = array_merge($items, $claims->toArray());
        }

        if ((! $typeFilter || $typeFilter === 'project-claim') && array_intersect($userRoles, ['admin', 'finance', 'super_admin'])) {
            $projectClaims = ProjectClaim::with('project:id,name', 'submittedBy:id,name')
                ->where('status', 'submitted')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($pc) => [
                    'type' => 'project-claim',
                    'id' => $pc->id,
                    'title' => $pc->title ?? '',
                    'subtitle' => $pc->project->name ?? '',
                    'amount' => (float) ($pc->amount ?? 0),
                    'submitter' => $pc->submittedBy->name ?? '',
                    'submitted_at' => $pc->submitted_at ?? $pc->created_at?->toDateTimeString() ?? '',
                    'status' => $pc->status,
                    'allowed_actions' => ['approve', 'reject'],
                ]);
            $items = array_merge($items, $projectClaims->toArray());
        }

        if ((! $typeFilter || $typeFilter === 'subcon-claim') && array_intersect($userRoles, ['admin', 'pm', 'finance', 'super_admin'])) {
            $subconClaims = SubcontractorClaim::with('projectSubcontractor.subcontractor:id,company_name', 'submitter:id,name')
                ->whereIn('status', ['submitted', 'verified'])
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($sc) => [
                    'type' => 'subcon-claim',
                    'id' => $sc->id,
                    'title' => 'Claim - '.($sc->projectSubcontractor->subcontractor->company_name ?? ''),
                    'subtitle' => $sc->period_start && $sc->period_end
                        ? ($sc->period_start->format('d M').' - '.$sc->period_end->format('d M'))
                        : '',
                    'amount' => (float) ($sc->net_payable ?? 0),
                    'submitter' => $sc->submitter->name ?? '',
                    'submitted_at' => $sc->submitted_at ?? $sc->created_at?->toDateTimeString() ?? '',
                    'status' => $sc->status,
                    'allowed_actions' => $sc->status === 'submitted'
                        ? ['verify', 'reject']
                        : (['approve', 'reject']),
                ]);
            $items = array_merge($items, $subconClaims->toArray());
        }

        if ((! $typeFilter || $typeFilter === 'timecard') && array_intersect($userRoles, ['admin', 'pm', 'super_admin'])) {
            $timecards = Timecard::where('status', 'pending')
                ->orderByDesc('created_at')
                ->get();
            $staffIds = $timecards->pluck('staff_id')->unique()->filter()->toArray();
            $staffMap = StaffProfile::whereIn('id', $staffIds)->get()->keyBy('id');
            $mapped = $timecards->map(function ($t) use ($staffMap) {
                $staff = $staffMap->get($t->staff_id);
                $name = $staff->name ?? 'Staff #'.$t->staff_id;

                return [
                    'type' => 'timecard',
                    'id' => $t->id,
                    'title' => $name,
                    'subtitle' => ($t->date ?? '').' · '.($t->hours_worked ?? 0).'h',
                    'amount' => null,
                    'submitter' => $name,
                    'submitted_at' => $t->created_at?->toDateTimeString() ?? '',
                    'status' => $t->status,
                    'allowed_actions' => ['approve', 'reject'],
                ];
            });
            $items = array_merge($items, $mapped->toArray());
        }

        if ((! $typeFilter || $typeFilter === 'self-billed') && array_intersect($userRoles, ['admin', 'finance', 'super_admin'])) {
            $invoices = SelfBilledInvoice::with('supplier:id,company_name')
                ->where('status', 'draft')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($inv) => [
                    'type' => 'self-billed',
                    'id' => $inv->id,
                    'title' => $inv->invoice_number ?? '',
                    'subtitle' => $inv->supplier->company_name ?? '',
                    'amount' => (float) ($inv->total ?? 0),
                    'submitter' => '',
                    'submitted_at' => $inv->created_at?->toDateTimeString() ?? '',
                    'status' => $inv->status,
                    'allowed_actions' => ['approve'],
                ]);
            $items = array_merge($items, $invoices->toArray());
        }

        usort($items, fn ($a, $b) => strcmp($b['submitted_at'], $a['submitted_at']));

        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($params['per_page'] ?? 20)));
        $total = count($items);
        $offset = ($page - 1) * $perPage;
        $paginated = array_slice($items, $offset, $perPage);

        return response()->json([
            'data' => $paginated,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }
}
