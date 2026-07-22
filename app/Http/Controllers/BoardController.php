<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectClaim;
use App\Models\ProjectMaterialUsage;
use App\Models\Quotation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $params = $request->all();
        $query = Project::query();

        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('client', 'like', "%{$s}%")
                    ->orWhere('project_code', 'like', "%{$s}%")
                    ->orWhere('po_number', 'like', "%{$s}%")
                    ->orWhere('location', 'like', "%{$s}%");
            });
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['project_type_id'])) {
            $query->whereHas('projectTypes', function ($q) use ($params) {
                $q->where('project_type_id', $params['project_type_id']);
            });
        }

        if (! empty($params['group_id'])) {
            $query->whereHas('groups', fn ($q) => $q->where('project_group_id', $params['group_id']));
        }

        if (! empty($params['period_year'])) {
            $year = (int) $params['period_year'];
            $start = "{$year}-01-01";
            $end = "{$year}-12-31";
            if (! empty($params['period_quarter'])) {
                $q = (int) $params['period_quarter'];
                $start = date('Y-m-d', strtotime("{$year}-".(($q - 1) * 3 + 1).'-01'));
                $end = date('Y-m-t', strtotime("{$year}-".($q * 3).'-01'));
            }
            $query->where(function ($q) use ($end) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $end);
            })->where(function ($q) use ($start) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $start);
            });
        }

        $total = (clone $query)->count();
        $active = (clone $query)->whereIn('status', ['active', 'planning'])->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $totalBudget = (clone $query)->sum('budget_amount');
        $completionRate = $total > 0 ? round(($completed / $total) * 100) : 0;

        $allProjects = $query->get(['id', 'status', 'end_date']);
        $healthCounts = ['success' => 0, 'warning' => 0, 'error' => 0, 'late' => 0];
        foreach ($allProjects as $p) {
            $healthCounts[$this->calculateHealth($p->status, $p->end_date)]++;
        }

        return response()->json([
            'active_projects' => $active,
            'total_projects' => $total,
            'completion_rate' => $completionRate,
            'total_budget' => (float) $totalBudget,
            'health_counts' => $healthCounts,
        ]);
    }

    public function projects(Request $request): JsonResponse
    {
        $params = $request->all();
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($params['per_page'] ?? 15)));

        $query = Project::with(['phases', 'projectTypes', 'groups']);

        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('client', 'like', "%{$s}%")
                    ->orWhere('project_code', 'like', "%{$s}%")
                    ->orWhere('po_number', 'like', "%{$s}%")
                    ->orWhere('location', 'like', "%{$s}%");
            });
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['project_type_id'])) {
            $query->whereHas('projectTypes', function ($q) use ($params) {
                $q->where('project_type_id', $params['project_type_id']);
            });
        }

        if (! empty($params['group_id'])) {
            $query->whereHas('groups', fn ($q) => $q->where('project_group_id', $params['group_id']));
        }

        $projects = $query->get();

        $portfolio = $projects->map(function ($p) {
            $phases = $p->phases ?? collect();
            $total = $phases->count();
            $done = $phases->where('status', 'completed')->count();
            $progress = $total > 0 ? round(($done / $total) * 100) : 0;

            $health = $this->calculateHealth($p->status, $p->end_date);

            return [
                'id' => $p->id,
                'name' => $p->name,
                'project_code' => $p->project_code,
                'po_number' => $p->po_number,
                'client' => $p->client,
                'status' => $p->status,
                'health' => $health,
                'budget_amount' => (float) ($p->budget_amount ?? 0),
                'start_date' => $p->start_date,
                'end_date' => $p->end_date,
                'progress' => $progress,
                'phase_count' => $total,
                'phases_completed' => $done,
                'project_types' => $p->projectTypes->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color])->values(),
                'groups' => $p->groups->map(fn ($g) => ['id' => $g->id, 'name' => $g->name, 'color' => $g->color])->values(),
            ];
        });

        $sortBy = $params['sort_by'] ?? null;
        $sortDir = strtolower($params['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['name', 'status', 'budget_amount', 'progress', 'end_date'];

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $sorted = $portfolio->sortBy($sortBy, SORT_REGULAR, $sortDir === 'desc')->values();
        } else {
            $healthOrder = ['error' => 0, 'warning' => 1, 'late' => 2, 'success' => 99];
            $sorted = $portfolio->sort(function ($a, $b) use ($healthOrder) {
                $hCmp = ($healthOrder[$a['health']] ?? 99) <=> ($healthOrder[$b['health']] ?? 99);
                if ($hCmp !== 0) {
                    return $hCmp;
                }
                $aEnd = $a['end_date'] ?? '9999-12-31';
                $bEnd = $b['end_date'] ?? '9999-12-31';

                return $aEnd <=> $bEnd;
            })->values();
        }

        $total = $sorted->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;
        $items = $sorted->slice($offset, $perPage)->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    public function project(Request $request, int $id): JsonResponse
    {
        $project = Project::with([
            'phases.startedBy:id,name',
            'phases.completedBy:id,name',
            'phases.tasks.staff' => fn ($q) => $q->select('id', 'name'),
            'phases.tasks.documents',
            'phases.comments.staff:id,name',
            'projectTypes', 'groups', 'clientRelation', 'clientPic', 'staffPics', 'projectManager',
        ])->findOrFail($id);

        $materialTotal = (float) ProjectMaterialUsage::where('project_id', $project->id)->sum('total_cost');
        $claimsTotal = (float) ProjectClaim::where('project_id', $project->id)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');
        $totalSpent = $materialTotal + $claimsTotal;

        $phases = $project->phases->map(function ($ph) {
            return [
                'id' => $ph->id,
                'name' => $ph->name,
                'status' => $ph->status,
                'order' => $ph->order,
                'start_date' => $ph->start_date,
                'end_date' => $ph->end_date,
                'started_at' => $ph->started_at,
                'completed_at' => $ph->completed_at,
                'completion_remarks' => $ph->completion_remarks,
                'started_by' => $ph->startedBy ? ['id' => $ph->startedBy->id, 'name' => $ph->startedBy->name] : null,
                'completed_by' => $ph->completedBy ? ['id' => $ph->completedBy->id, 'name' => $ph->completedBy->name] : null,
                'task_count' => $ph->tasks->count(),
                'tasks_completed' => $ph->tasks->where('status', 'completed')->count(),
                'tasks' => $ph->tasks->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'status' => $t->status,
                    'priority' => $t->priority,
                    'pause_reason' => $t->pause_reason,
                    'start_date' => $t->start_date,
                    'end_date' => $t->end_date,
                    'actual_start' => $t->actual_start,
                    'actual_end' => $t->actual_end,
                    'staff' => $t->staff->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
                    'document_count' => $t->documents->count(),
                ])->values(),
                'comments' => $ph->comments->map(fn ($c) => [
                    'id' => $c->id,
                    'body' => $c->body,
                    'created_at' => $c->created_at,
                    'staff' => ['id' => $c->staff->id ?? 0, 'name' => $c->staff->name ?? 'Unknown'],
                ])->values(),
            ];
        });

        $totalPhaseCount = $phases->count();
        $completedPhaseCount = $phases->where('status', 'completed')->count();
        $progress = $totalPhaseCount > 0 ? round(($completedPhaseCount / $totalPhaseCount) * 100) : 0;

        $budget = (float) ($project->budget_amount ?? 0);

        $client = $project->clientRelation;
        $clientPic = $project->clientPic;
        $staffPics = $project->staffPics->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'employee_id' => $s->employee_id,
            'job_title' => $s->job_title,
            'phone' => $s->phone,
            'email' => $s->email,
        ])->values();
        $pm = $project->projectManager;

        return response()->json([
            'id' => $project->id,
            'name' => $project->name,
            'project_code' => $project->project_code,
            'po_number' => $project->po_number,
            'location' => $project->location,
            'client' => $project->client,
            'status' => $project->status,
            'budget_amount' => $budget,
            'total_spent' => $totalSpent,
            'start_date' => $project->start_date,
            'end_date' => $project->end_date,
            'progress' => $progress,
            'phases' => $phases,
            'phase_count' => $totalPhaseCount,
            'phases_completed' => $completedPhaseCount,
            'project_types' => $project->projectTypes->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color])->values(),
            'groups' => $project->groups->map(fn ($g) => ['id' => $g->id, 'name' => $g->name, 'color' => $g->color])->values(),
            'client_info' => $client ? [
                'id' => $client->id,
                'company_name' => $client->company_name,
                'registration_no' => $client->registration_no,
                'phone' => $client->phone,
                'email' => $client->email,
                'address' => $client->address,
            ] : null,
            'client_pic' => $clientPic ? [
                'id' => $clientPic->id,
                'name' => $clientPic->name,
                'phone' => $clientPic->phone,
                'email' => $clientPic->email,
                'job_title' => $clientPic->job_title,
            ] : null,
            'staff_pics' => $staffPics,
            'project_manager' => $pm ? [
                'id' => $pm->id,
                'name' => $pm->name,
                'employee_id' => $pm->employee_id,
                'job_title' => $pm->job_title,
                'phone' => $pm->phone,
                'email' => $pm->email,
            ] : null,
            'quotations' => Quotation::where('project_id', $project->id)->select(['id', 'quote_number', 'status', 'total', 'date'])->get(),
            'contracts' => Contract::where('project_id', $project->id)->select(['id', 'contract_number', 'status', 'total_amount', 'date'])->get(),
            'invoices' => Invoice::where('project_id', $project->id)->select(['id', 'invoice_number', 'status', 'total', 'date'])->get(),
        ]);
    }

    private function calculateHealth(string $status, $endDate): string
    {
        if ($status === 'paused' || $status === 'cancelled') {
            return 'error';
        }
        if ($endDate && Carbon::now()->gt($endDate)) {
            return $status === 'completed' ? 'late' : 'warning';
        }
        if ($status === 'draft') {
            return 'warning';
        }

        return 'success';
    }
}
