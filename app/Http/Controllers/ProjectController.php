<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Phase;
use App\Models\PhaseTemplate;
use App\Models\Project;
use App\Models\ProjectMaterialUsage;
use App\Models\Quotation;
use App\Models\StaffProfile;
use App\Services\Notification\NotificationEvent;
use App\Services\NumberingService;
use App\Traits\NotifiesProjectParticipants;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use NotifiesProjectParticipants;

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = Project::with('phases', 'clientRelation', 'clientPic', 'staffPics', 'projectTypes', 'groups');
        $this->applyFilters($query, $params);

        if (! $this->isPmPlus($request)) {
            $staff = $this->getStaffFromRequest($request);
            if ($staff) {
                $query->whereHas('staffPics', fn ($q) => $q->where('staff_id', $staff->id));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (! empty($params['all']) && $params['all'] === 'true') {
            return response()->json(['data' => $query->orderBy('name')->get()]);
        }

        return $this->paginate($query, $params, [
            'sortable' => ['project_code', 'name', 'client', 'start_date', 'end_date', 'status', 'location', 'created_at'],
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = Project::query();
        $this->applyFilters($query, $params);

        $total = (clone $query)->count();
        $active = (clone $query)->whereIn('status', ['active', 'planning'])->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $totalBudget = (clone $query)->sum('budget_amount');
        $totalClaimed = (clone $query)->selectRaw('COALESCE(SUM(project_claims.amount), 0) as total')
            ->join('project_claims', 'projects.id', '=', 'project_claims.project_id')
            ->whereIn('project_claims.status', ['approved', 'paid'])
            ->pluck('total')
            ->first();
        $completionRate = $total > 0 ? round(($completed / $total) * 100) : 0;

        $healthCounts = ['success' => 0, 'warning' => 0, 'error' => 0, 'late' => 0];
        foreach ($query->get(['status', 'end_date']) as $p) {
            $health = 'success';
            if ($p->status === 'paused' || $p->status === 'cancelled') {
                $health = 'error';
            } elseif ($p->end_date && Carbon::now()->gt($p->end_date)) {
                $health = $p->status === 'completed' ? 'late' : 'warning';
            } elseif ($p->status === 'draft') {
                $health = 'warning';
            }
            $healthCounts[$health]++;
        }

        return response()->json([
            'total' => $total,
            'active' => $active,
            'completed' => $completed,
            'completion_rate' => $completionRate,
            'total_budget' => (float) $totalBudget,
            'total_claimed' => (float) ($totalClaimed ?? 0),
            'health_counts' => $healthCounts,
        ]);
    }

    public function costSummary(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $materials = (float) ProjectMaterialUsage::where('project_id', $project->id)->sum('total_cost');

        $claims = (float) $project->claims()
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        $labor = (float) Attendance::where('project_id', $project->id)
            ->whereNotNull('clock_out')
            ->join('staff_profiles', 'attendance.staff_id', '=', 'staff_profiles.id')
            ->selectRaw('COALESCE(SUM(attendance.total_hours * staff_profiles.hourly_rate), 0) as total')
            ->pluck('total')
            ->first();

        $budget = (float) ($project->budget_amount ?? 0);
        $totalCost = $materials + $claims + $labor;
        $remaining = max(0, $budget - $totalCost);

        return response()->json([
            'budget' => $budget,
            'costs' => [
                'materials' => $materials,
                'claims' => $claims,
                'labor' => $labor,
                'total_cost' => $totalCost,
            ],
            'remaining' => $remaining,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (! isset($data['name']) || empty(trim($data['name']))) {
            return response()->json(['error' => 'name is required'], 422);
        }
        if (empty($data['client_id']) && (empty($data['client']) || empty(trim($data['client'])))) {
            return response()->json(['error' => 'client_id or client name is required'], 422);
        }
        $allowed = ['draft', 'planning', 'active', 'paused', 'completed', 'cancelled'];
        if (isset($data['status']) && ! in_array($data['status'], $allowed)) {
            return response()->json(['error' => 'invalid status, allowed: '.implode(', ', $allowed)], 422);
        }

        $data['project_code'] = (new NumberingService)->generate('project');

        if (! empty($data['client_id']) && empty($data['client'])) {
            $client = Client::find($data['client_id']);
            if ($client) {
                $data['client'] = $client->company_name;
            }
        }

        $typeIds = $data['project_type_ids'] ?? [];
        unset($data['project_type_ids']);

        $groupIds = $data['project_group_ids'] ?? [];
        unset($data['project_group_ids']);

        $item = Project::create(fillableData(new Project, $data));

        if (! empty($groupIds)) {
            $item->groups()->sync($groupIds);
        }

        if (! empty($typeIds)) {
            $item->projectTypes()->sync($typeIds);

            $templates = PhaseTemplate::select('phase_templates.*')
                ->join('project_type_phase_template', 'phase_templates.id', '=', 'project_type_phase_template.phase_template_id')
                ->whereIn('project_type_phase_template.project_type_id', $typeIds)
                ->groupBy('phase_templates.id')
                ->orderByRaw('MIN(project_type_phase_template.sort_order)')
                ->get();

            $phaseCount = $templates->count();
            $projectStart = $item->start_date ? strtotime($item->start_date) : null;
            $projectEnd = $item->end_date ? strtotime($item->end_date) : null;

            foreach ($templates as $i => $t) {
                $phaseStart = null;
                $phaseEnd = null;
                if ($projectStart && $projectEnd && $phaseCount > 0) {
                    $totalDays = max(1, ($projectEnd - $projectStart) / 86400);
                    $daysPerPhase = $totalDays / $phaseCount;
                    $phaseStart = date('Y-m-d', $projectStart + (int) ($i * $daysPerPhase * 86400));
                    $phaseEnd = date('Y-m-d', $projectStart + (int) (($i + 1) * $daysPerPhase * 86400));
                }
                Phase::create([
                    'project_id' => $item->id,
                    'name' => $t->name,
                    'order' => $t->order,
                    'status' => 'pending',
                    'start_date' => $phaseStart,
                    'end_date' => $phaseEnd,
                ]);
            }
        }

        $this->notifyProject(
            NotificationEvent::PROJECT_CREATED,
            ['project_name' => $item->name],
            $item->id,
            'App\\Models\\Project',
            $item->id,
            'New Project: '.$item->name,
            'Project '.$item->name.' ('.$item->project_code.') has been created.',
            '/projects/'.$item->id
        );

        $item->load('clientRelation', 'clientPic', 'staffPics', 'projectManager', 'projectTypes', 'groups');

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (! $this->isProjectMember($request, $id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $item = Project::with('phases', 'clientRelation', 'clientPic', 'staffPics', 'projectManager', 'projectTypes', 'groups')->findOrFail($id);
        $item->claimed_amount = (float) $item->claims()
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');
        $result = $item->toArray();
        $result['quotations'] = Quotation::where('project_id', $item->id)
            ->select(['id', 'quote_number', 'status', 'total', 'date'])->get();
        $result['contracts'] = Contract::where('project_id', $item->id)
            ->select(['id', 'contract_number', 'status', 'total_amount', 'date'])->get();
        $result['invoices'] = Invoice::where('project_id', $item->id)
            ->select(['id', 'invoice_number', 'status', 'total', 'date'])->get();

        return response()->json($result);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = Project::findOrFail($id);
        $data = $request->all();
        $allowed = ['draft', 'planning', 'active', 'paused', 'completed', 'cancelled'];
        if (isset($data['status']) && ! in_array($data['status'], $allowed)) {
            return response()->json(['error' => 'invalid status, allowed: '.implode(', ', $allowed)], 422);
        }
        if (! empty($data['client_id']) && (! isset($data['client']) || empty($data['client']))) {
            $client = Client::find($data['client_id']);
            if ($client) {
                $data['client'] = $client->company_name;
            }
        }

        if (isset($data['project_type_ids'])) {
            $item->projectTypes()->sync($data['project_type_ids']);
            unset($data['project_type_ids']);
        }
        if (isset($data['project_group_ids'])) {
            $item->groups()->sync($data['project_group_ids']);
            unset($data['project_group_ids']);
        }

        $item->update(fillableData($item, $data));
        $item->load('projectTypes', 'groups');

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        Project::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    private function applyFilters(Builder $query, array $params): void
    {
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

        if (! empty($params['client_id'])) {
            $query->where('client_id', $params['client_id']);
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
    }

    private function paginate(Builder $query, array $params, array $extra = []): JsonResponse
    {
        $page = max(1, intval($params['page'] ?? 1));
        $perPage = min(100, max(1, intval($params['per_page'] ?? 15)));

        $sortable = $extra['sortable'] ?? ['created_at'];
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortDir = strtolower($params['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, $sortable)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $total = $query->count();
        $items = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $result = [
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ];

        foreach ($extra as $key => $val) {
            $result[$key] = $val;
        }

        return response()->json($result);
    }

    private function isPmPlus(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }
        $roles = $user->getRoleNames();

        return (bool) array_intersect($roles, ['admin', 'pm', 'super_admin']);
    }

    private function getStaffFromRequest(Request $request): ?StaffProfile
    {
        $user = $request->user();
        if (! $user || empty($user->email)) {
            return null;
        }

        return StaffProfile::where('email', $user->email)->first();
    }

    private function isProjectMember(Request $request, int $projectId): bool
    {
        if ($this->isPmPlus($request)) {
            return true;
        }

        $staff = $this->getStaffFromRequest($request);
        if (! $staff) {
            return false;
        }

        if (Project::find($projectId)?->staffPics()
            ->where('staff_id', $staff->id)
            ->exists()) {
            return true;
        }

        return Phase::where('project_id', $projectId)
            ->whereHas('tasks.staff', fn ($q) => $q->where('staff_id', $staff->id))
            ->exists();
    }
}
