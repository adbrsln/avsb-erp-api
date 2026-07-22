<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Phase;
use App\Models\PhaseComment;
use App\Models\Project;
use App\Models\StaffProfile;
use App\Models\Task;
use App\Services\Notification\NotificationEvent;
use App\Services\NumberingService;
use App\Traits\NotifiesProjectParticipants;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PhaseController extends Controller
{
    use NotifiesProjectParticipants;

    public function save(Request $request): JsonResponse
    {
        $data = $request->all();

        try {
            $phase = DB::transaction(function () use ($data) {
                if (! empty($data['id'])) {
                    $phase = Phase::findOrFail($data['id']);
                    $phase->update(fillableData($phase, $data));
                } else {
                    $phase = Phase::create(fillableData(new Phase, $data));
                }

                if (isset($data['staff_ids'])) {
                    $phase->staff()->sync($data['staff_ids']);
                } else {
                    $phase->staff()->sync([]);
                }

                $existingIds = $phase->tasks()->pluck('id')->all();
                $submittedIds = array_filter(array_column($data['tasks'] ?? [], 'id'));
                $removedIds = array_diff($existingIds, $submittedIds);
                if ($removedIds) {
                    Task::whereIn('id', $removedIds)->delete();
                }

                foreach ($data['tasks'] ?? [] as $taskData) {
                    $staffIds = $taskData['staff_ids'] ?? [];
                    unset($taskData['staff_ids'], $taskData['key']);

                    if (! empty($taskData['id'])) {
                        $task = Task::findOrFail($taskData['id']);
                        $task->update(fillableData($task, $taskData));
                    } else {
                        $taskData['phase_id'] = $phase->id;
                        $task = Task::create(fillableData(new Task, $taskData));
                    }
                    $task->staff()->sync($staffIds);
                }

                $phase->load('staff', 'tasks.staff');

                return $phase;
            });

            return response()->json(['data' => $phase]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'save_failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = Phase::with([
            'checklistItems',
            'checklistResults',
            'startedBy:id,name',
            'completedBy:id,name',
            'documents',
            'tasks',
            'tasks.staff' => fn ($q) => $q->select('id', 'name'),
            'tasks.documents',
            'tasks.startedBy:id,name',
            'tasks.pausedBy:id,name',
            'tasks.completedBy:id,name',
            'comments.staff:id,name',
            'staff' => fn ($q) => $q->select('id', 'name'),
        ]);
        if (! empty($params['project_id'])) {
            if (! $this->isProjectMember($request, (int) $params['project_id'])) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            $query->where('project_id', $params['project_id']);
        }

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (! isset($data['name']) || empty(trim($data['name']))) {
            return response()->json(['error' => 'name is required'], 422);
        }
        if (! isset($data['project_id']) || empty($data['project_id'])) {
            return response()->json(['error' => 'project_id is required'], 422);
        }

        $item = Phase::create(fillableData(new Phase, $data));

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = Phase::with('checklistItems', 'checklistResults')->findOrFail($id);
        if (! $this->isAssignedToPhase($request, $item)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json($item);
    }

    public function comments(Request $request, int $id): JsonResponse
    {
        $phase = Phase::findOrFail($id);
        if (! $this->isAssignedToPhase($request, $phase)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $comments = PhaseComment::with('staff:id,name')
            ->where('phase_id', $phase->id)
            ->orderBy('created_at')
            ->get();

        return response()->json(['data' => $comments]);
    }

    public function addComment(Request $request, int $id): JsonResponse
    {
        $phase = Phase::findOrFail($id);
        if (! $this->isAssignedToPhase($request, $phase)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $data = $request->all();
        if (empty($data['body'])) {
            return response()->json(['error' => 'body is required'], 422);
        }
        $staff = $this->getStaffFromRequest($request);

        $comment = PhaseComment::create([
            'phase_id' => $phase->id,
            'staff_id' => $staff?->id,
            'body' => $data['body'],
        ]);
        $comment->load('staff:id,name');

        $this->notifyProject(
            'phase.comment_added',
            ['phase_name' => $phase->name, 'staff_name' => $staff?->name ?? 'Someone'],
            $phase->project_id,
            'App\\Models\\Phase',
            $phase->id,
            'New note: '.$phase->name,
            ($staff?->name ?? 'Someone').' added a note to phase '.$phase->name.'.',
            '/projects/'.$phase->project_id
        );

        return response()->json($comment, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = Phase::findOrFail($id);
        if (! $this->isProjectMember($request, $item->project_id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->all();
        if ($item->status === 'completed' && isset($data['status']) && $data['status'] !== 'completed' && ! $this->isPmPlus($request)) {
            return response()->json(['error' => 'Only a PM can reopen a completed phase'], 422);
        }

        $oldStatus = $item->getOriginal('status');
        $item->update(fillableData($item, $data));
        $item->refresh();

        if ($oldStatus === 'completed' && $item->status !== 'completed') {
            $project = $item->project;
            if ($project && $project->status === 'completed') {
                $project->update(['status' => 'in_progress']);
            }
        }

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $roles = $user?->getRoleNames() ?? [];
        if (! array_intersect($roles, ['admin', 'pm', 'super_admin'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        Phase::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    public function checklistItems(Request $request, int $id): JsonResponse
    {
        $phase = Phase::findOrFail($id);

        return response()->json($phase->checklistItems);
    }

    public function checklistResults(Request $request, int $id): JsonResponse
    {
        $phase = Phase::findOrFail($id);

        return response()->json($phase->checklistResults);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $phase = Phase::findOrFail($id);
        if (! $this->isAssignedToPhase($request, $phase)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->all();
        if (! isset($data['status'])) {
            return response()->json(['error' => 'status is required'], 422);
        }

        if ($phase->status === 'completed' && $data['status'] !== 'completed' && ! $this->isPmPlus($request)) {
            return response()->json(['error' => 'Only a PM can reopen a completed phase'], 422);
        }

        $phase->update(['status' => $data['status']]);

        return response()->json($phase);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['phase_id']) || empty($data['direction'])) {
            return response()->json(['error' => 'phase_id and direction are required'], 422);
        }

        $phase = Phase::findOrFail($data['phase_id']);
        $projectId = $phase->project_id;
        $currentOrder = $phase->order;

        $neighbor = $data['direction'] === 'up'
            ? Phase::where('project_id', $projectId)->where('order', '<', $currentOrder)->orderBy('order', 'desc')->first()
            : Phase::where('project_id', $projectId)->where('order', '>', $currentOrder)->orderBy('order')->first();

        if (! $neighbor) {
            return response()->json(['error' => 'Cannot move further'], 422);
        }

        $phase->update(['order' => $neighbor->order]);
        $neighbor->update(['order' => $currentOrder]);

        return response()->json(['success' => true]);
    }

    public function reorderBatch(Request $request): JsonResponse
    {
        $data = $request->all();
        DB::transaction(function () use ($data) {
            foreach ($data['phase_ids'] ?? [] as $order => $id) {
                $phase = Phase::find($id);
                if ($phase) {
                    $phase->update(['order' => $order + 1]);
                }
            }
        });

        return response()->json(['success' => true]);
    }

    public function start(Request $request, int $id): JsonResponse
    {
        $phase = Phase::findOrFail($id);
        if (! $this->isAssignedToPhase($request, $phase)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $staff = $this->getStaffFromRequest($request);

        $phase->update([
            'status' => 'in_progress',
            'started_by' => $staff?->id,
            'started_at' => date('Y-m-d H:i:s'),
        ]);

        $project = $phase->project;
        if ($project && in_array($project->status, ['draft', 'planning', 'pending'])) {
            $project->update(['status' => 'active']);
        }

        $phase->load('startedBy');

        $this->notifyProject(
            NotificationEvent::PHASE_STARTED,
            ['phase_name' => $phase->name, 'project_name' => ($phase->project?->name ?? 'Unknown')],
            $phase->project_id,
            'App\\Models\\Phase',
            $phase->id,
            'Phase Started: '.$phase->name,
            'Phase '.$phase->name.' has been started.',
            '/projects/'.$phase->project_id
        );

        return response()->json($phase);
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $phase = Phase::with('tasks')->findOrFail($id);
        if (! $this->isAssignedToPhase($request, $phase)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $data = $request->all();
        $staff = $this->getStaffFromRequest($request);

        $incomplete = $phase->tasks->where('status', '!=', 'completed')->count();
        $force = $data['force'] ?? false;
        if ($incomplete > 0 && ! $force) {
            return response()->json([
                'error' => 'tasks_pending',
                'message' => "{$incomplete} task(s) still incomplete. Set force=true to override.",
                'count' => $incomplete,
                'allow_force' => true,
            ], 422);
        }

        $phase->update([
            'status' => 'completed',
            'completed_by' => $staff?->id,
            'completed_at' => $data['completed_at'] ?? date('Y-m-d H:i:s'),
            'completion_remarks' => $data['remarks'] ?? null,
        ]);

        if ($force && $incomplete > 0) {
            $now = date('Y-m-d H:i:s');
            $phase->tasks()->where('status', '!=', 'completed')->update([
                'status' => 'completed',
                'actual_start' => $now,
                'actual_end' => $now,
                'completion_notes' => 'Auto-completed via phase force-complete',
            ]);
        }

        $allCompleted = Phase::where('project_id', $phase->project_id)
            ->where('status', '!=', 'completed')
            ->count() === 0;
        if ($allCompleted) {
            $project = Project::find($phase->project_id);
            if ($project) {
                $project->update(['status' => 'completed']);
            }

            try {
                if ($project && ($project->budget_amount ?? 0) > 0 && ! Invoice::where('project_id', $project->id)->exists()) {
                    $budget = (float) $project->budget_amount;
                    $clientName = $project->client ?? ($project->clientRelation->company_name ?? '');
                    $invoice = Invoice::create([
                        'invoice_number' => (new NumberingService)->generate('invoice'),
                        'project_id' => $project->id,
                        'client' => $clientName,
                        'date' => date('Y-m-d'),
                        'due_date' => date('Y-m-d', strtotime('+30 days')),
                        'status' => 'draft',
                        'subtotal' => $budget,
                        'sst' => 0,
                        'retention' => 0,
                        'total' => $budget,
                        'items' => [
                            ['description' => 'Project Completion - '.$project->name, 'amount' => $budget],
                        ],
                    ]);

                    $revenueAccount = ChartOfAccount::where('code', '4101')->first();
                    $arAccount = ChartOfAccount::where('code', '1104')->first();
                    if ($revenueAccount && $arAccount) {
                        $je = JournalEntry::create([
                            'entry_number' => (new NumberingService)->generate('journal'),
                            'entry_date' => date('Y-m-d'),
                            'description' => 'Invoice from project completion - '.$project->name,
                            'reference_type' => 'invoice',
                            'reference_id' => $invoice->id,
                            'status' => 'posted',
                            'posted_at' => date('Y-m-d H:i:s'),
                        ]);
                        JournalEntryLine::create([
                            'journal_entry_id' => $je->id,
                            'account_id' => $arAccount->id,
                            'debit' => $budget,
                            'description' => $invoice->invoice_number,
                        ]);
                        JournalEntryLine::create([
                            'journal_entry_id' => $je->id,
                            'account_id' => $revenueAccount->id,
                            'credit' => $budget,
                            'description' => $invoice->invoice_number,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Auto-invoice failed', ['project_id' => $phase->project_id, 'error' => $e->getMessage()]);
            }

            $this->notifyProject(
                NotificationEvent::PROJECT_COMPLETED,
                ['project_name' => ($phase->project?->name ?? 'Unknown')],
                $phase->project_id,
                'App\\Models\\Project',
                $phase->project?->id,
                'Project Completed: '.($phase->project?->name ?? 'Unknown'),
                'Project has been completed.',
                '/projects/'.$phase->project_id
            );
        }

        $phase->load('completedBy');

        $this->notifyProject(
            NotificationEvent::PHASE_COMPLETED,
            ['phase_name' => $phase->name, 'project_name' => ($phase->project?->name ?? 'Unknown')],
            $phase->project_id,
            'App\\Models\\Phase',
            $phase->id,
            'Phase Completed: '.$phase->name,
            'Phase '.$phase->name.' has been completed.',
            '/projects/'.$phase->project_id
        );

        return response()->json($phase);
    }

    private function isAssignedToPhase(Request $request, Phase $phase): bool
    {
        if ($this->isProjectMember($request, $phase->project_id)) {
            return true;
        }
        $staff = $this->getStaffFromRequest($request);
        if (! $staff) {
            return false;
        }

        return $phase->tasks()
            ->whereHas('staff', fn ($q) => $q->where('staff_id', $staff->id))
            ->exists();
    }

    private function paginate(Builder $query, array $params): JsonResponse
    {
        $page = max(1, intval($params['page'] ?? 1));
        $perPage = min(100, max(1, intval($params['per_page'] ?? 15)));

        $total = $query->count();
        $items = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    private function isPmPlus(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        return (bool) array_intersect($user->getRoleNames(), ['admin', 'pm', 'super_admin']);
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
