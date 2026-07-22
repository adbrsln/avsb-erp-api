<?php

namespace App\Http\Controllers;

use App\Models\Phase;
use App\Models\StaffProfile;
use App\Models\Task;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationService;
use App\Traits\NotifiesProjectParticipants;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    use NotifiesProjectParticipants;

    public function index(Request $request, ?int $id = null): JsonResponse
    {
        $params = $request->query();
        $query = Task::with(['staff' => fn ($q) => $q->select('id', 'name')]);
        $phaseId = $id ?? $params['phase_id'] ?? null;
        if ($phaseId) {
            $query->where('phase_id', $phaseId);
        }
        if (isset($params['assigned_to'])) {
            $query->where('assigned_to', $params['assigned_to']);
        }

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();
        if (! isset($data['title']) || trim($data['title']) === '') {
            return response()->json(['error' => 'title is required'], 422);
        }
        if (! isset($data['phase_id'])) {
            return response()->json(['error' => 'phase_id is required'], 422);
        }
        $item = Task::create(fillableData(new Task, $data));

        try {
            $phase = Phase::with('project')->find($data['phase_id'] ?? 0);
            $projectName = $phase?->project?->name ?? 'Unknown';
            $assignedStaff = [];
            if (! empty($data['staff_ids'])) {
                $assignedStaff = StaffProfile::whereIn('id', (array) $data['staff_ids'])->get();
            } elseif (! empty($data['assigned_to'])) {
                $assignedStaff = StaffProfile::where('id', $data['assigned_to'])->get();
            }
            foreach ($assignedStaff as $s) {
                NotificationService::queue(
                    NotificationEvent::TASK_ASSIGNED,
                    $s->email,
                    $s->name,
                    [
                        'task_title' => $item->title,
                        'project_name' => $projectName,
                        'url' => '/tasks',
                    ],
                    'App\\Models\\Task',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Notification failed: task.assigned', ['task_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = Task::findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        if (isset($data['title']) && trim($data['title']) === '') {
            return response()->json(['error' => 'title cannot be empty'], 422);
        }
        $item = Task::findOrFail($id);
        $item->update(fillableData($item, $data));

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        Task::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    public function start(Request $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        if (! $this->isAssigned($request, $task)) {
            return response()->json(['error' => 'Forbidden: task not assigned to you'], 403);
        }
        if (! in_array($task->status, ['todo', 'paused'])) {
            return response()->json(['error' => 'Task can only be started from "todo" or "paused" status'], 422);
        }

        $staff = $this->getStaff($request);

        $task->update(['status' => 'running', 'actual_start' => date('Y-m-d H:i:s'), 'started_by' => $staff?->id]);

        $phase = $task->phase;
        if ($phase && ! $phase->started_at) {
            $hasOtherStarted = $phase->tasks()
                ->where('id', '!=', $task->id)
                ->whereNotNull('actual_start')
                ->exists();
            if (! $hasOtherStarted) {
                $phase->update([
                    'status' => 'in_progress',
                    'started_at' => date('Y-m-d H:i:s'),
                    'started_by' => $staff?->id,
                ]);
            }
        }

        $projectId = $task->phase?->project_id;
        if ($projectId) {
            $this->notifyProject(
                'task.started',
                ['task_title' => $task->title, 'project_name' => $task->phase?->project?->name ?? ''],
                $projectId, 'App\\Models\\Task', $task->id,
                'Task Started: '.$task->title,
                'Task '.$task->title.' has been started.',
                '/projects/'.$projectId
            );
        }

        return response()->json($task);
    }

    public function pause(Request $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        if (! $this->isAssigned($request, $task)) {
            return response()->json(['error' => 'Forbidden: task not assigned to you'], 403);
        }
        if ($task->status !== 'running') {
            return response()->json(['error' => 'Only running tasks can be paused'], 422);
        }
        $staff = $this->getStaff($request);
        $data = $request->all();
        $task->update([
            'status' => 'paused',
            'pause_reason' => $data['reason'] ?? null,
            'pause_notes' => $data['notes'] ?? null,
            'paused_at' => date('Y-m-d H:i:s'),
            'paused_by' => $staff?->id,
        ]);

        $projectId = $task->phase?->project_id;
        if ($projectId) {
            $this->notifyProject(
                'task.paused',
                ['task_title' => $task->title, 'project_name' => $task->phase?->project?->name ?? ''],
                $projectId, 'App\\Models\\Task', $task->id,
                'Task Paused: '.$task->title,
                'Task '.$task->title.' has been paused.',
                '/projects/'.$projectId
            );
        }

        return response()->json($task);
    }

    public function resume(Request $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        if (! $this->isAssigned($request, $task)) {
            return response()->json(['error' => 'Forbidden: task not assigned to you'], 403);
        }
        if ($task->status !== 'paused') {
            return response()->json(['error' => 'Only paused tasks can be resumed'], 422);
        }
        $task->update(['status' => 'running', 'paused_at' => null, 'pause_reason' => null, 'paused_by' => null]);

        $projectId = $task->phase?->project_id;
        if ($projectId) {
            $this->notifyProject(
                'task.resumed',
                ['task_title' => $task->title, 'project_name' => $task->phase?->project?->name ?? ''],
                $projectId, 'App\\Models\\Task', $task->id,
                'Task Resumed: '.$task->title,
                'Task '.$task->title.' has been resumed.',
                '/projects/'.$projectId
            );
        }

        return response()->json($task);
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);

        $staff = $this->getStaff($request);

        if (! $this->isAssigned($request, $task)) {
            return response()->json(['error' => 'Forbidden: task not assigned to you'], 403);
        }
        if (! in_array($task->status, ['running', 'paused'])) {
            return response()->json(['error' => 'Only running or paused tasks can be completed'], 422);
        }
        $data = $request->all();
        $task->update([
            'status' => 'completed',
            'actual_start' => $task->actual_start ?? date('Y-m-d H:i:s'),
            'actual_end' => date('Y-m-d H:i:s'),
            'completion_notes' => $data['notes'] ?? null,
            'completed_by' => $staff?->id,
        ]);

        $phase = $task->phase;
        if ($phase && $phase->status !== 'completed') {
            $remaining = $phase->tasks()->where('status', '!=', 'completed')->count();
            if ($remaining === 0) {
                $phase->update([
                    'status' => 'completed',
                    'completed_by' => $staff?->id,
                    'completed_at' => date('Y-m-d H:i:s'),
                    'completion_remarks' => 'Auto-completed: all tasks finished',
                ]);
            }
        }

        $projectId = $task->phase?->project_id;
        if ($projectId) {
            $this->notifyProject(
                NotificationEvent::TASK_COMPLETED,
                ['task_title' => $task->title, 'project_name' => $task->phase?->project?->name ?? ''],
                $projectId, 'App\\Models\\Task', $task->id,
                'Task Completed: '.$task->title,
                'Task '.$task->title.' has been completed.',
                '/projects/'.$projectId
            );
        }

        return response()->json($task);
    }

    public function syncStaff(Request $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $data = $request->all();
        $staffIds = $data['staff_ids'] ?? [];
        $task->staff()->sync($staffIds);
        $task->load('staff');

        try {
            $phase = $task->phase;
            $projectName = $phase?->project?->name ?? 'Unknown';
            $newStaff = StaffProfile::whereIn('id', $staffIds)->get();
            foreach ($newStaff as $s) {
                NotificationService::queue(
                    NotificationEvent::TASK_ASSIGNED,
                    $s->email,
                    $s->name,
                    [
                        'task_title' => $task->title,
                        'project_name' => $projectName,
                        'url' => '/tasks',
                    ],
                    'App\\Models\\Task',
                    $task->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Notification failed: task.assigned', ['task_id' => $task->id, 'error' => $e->getMessage()]);
        }

        return response()->json(['data' => $task->staff]);
    }

    private function isAssigned(Request $request, Task $task): bool
    {
        $user = $request->user();
        $userRoles = $user?->getRoleNames() ?? [];
        if (array_intersect($userRoles, ['admin', 'pm', 'super_admin'])) {
            return true;
        }

        $staff = $this->getStaff($request);
        if (! $staff) {
            return false;
        }
        if ($task->staff()->where('staff_id', $staff->id)->exists()) {
            return true;
        }
        if ((int) $task->assigned_to === $staff->id) {
            return true;
        }
        if ($task->phase && $task->phase->staff()->where('staff_id', $staff->id)->exists()) {
            return true;
        }
        if ($task->phase && $task->phase->project && $task->phase->project->staffPics()->where('staff_id', $staff->id)->exists()) {
            return true;
        }

        return false;
    }

    private function getStaff(Request $request): ?StaffProfile
    {
        $user = $request->user();
        if (! $user || empty($user->email)) {
            return null;
        }

        return StaffProfile::where('email', $user->email)->first();
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
}
