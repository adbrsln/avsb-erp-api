<?php

namespace App\Http\Controllers;

use App\Models\LeaveGroup;
use App\Models\Phase;
use App\Models\Project;
use App\Models\StaffLeaveBalance;
use App\Models\StaffProfile;
use App\Models\Task;
use App\Models\User;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $query = StaffProfile::with(['leaveGroup', 'user'])
            ->whereDoesntHave('user.roles', fn ($q) => $q->where('role', 'super_admin'));

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if ($request->input('all') === 'true') {
            return response()->json(['data' => $query->orderBy('name')->get()]);
        }

        return $this->paginate($query, $request->all());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        $roles = $data['roles'] ?? [$data['role'] ?? 'staff'];
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $allowedRoles = ['staff', 'pm', 'hr', 'finance', 'admin', 'super_admin'];
        foreach ($roles as $r) {
            if (! in_array($r, $allowedRoles)) {
                return response()->json(['errors' => ["Invalid role: {$r}"]], 422);
            }
        }

        $data['employee_id'] = (new NumberingService)->generate('employee');
        $item = StaffProfile::create(fillableData(new StaffProfile, $data));

        $password = bin2hex(random_bytes(4));
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($password, PASSWORD_BCRYPT),
        ]);
        $user->syncRoles($roles);

        $result = $item->toArray();
        $result['generated_password'] = $password;
        $result['roles'] = $roles;

        return response()->json($result, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = StaffProfile::with('user')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $item = StaffProfile::findOrFail($id);
        $oldGroupId = $item->leave_group_id;

        $item->update(fillableData($item, $data));

        $user = User::where('email', $item->getOriginal('email'))->first();
        if ($user) {
            $userData = array_intersect_key($data, array_flip(['name', 'email']));
            if (! empty($userData)) {
                $user->update($userData);
            }
            if (isset($data['roles'])) {
                $roles = is_string($data['roles']) ? [$data['roles']] : $data['roles'];
                $user->syncRoles($roles);
            } elseif (isset($data['role'])) {
                $user->syncRoles([$data['role']]);
            }
        }

        if ($item->leave_group_id && $oldGroupId !== $item->leave_group_id) {
            $group = LeaveGroup::with('entitlements')->find($item->leave_group_id);
            if ($group) {
                $year = date('Y');
                foreach ($group->entitlements as $e) {
                    StaffLeaveBalance::firstOrCreate(
                        ['staff_id' => $item->id, 'type' => $e->type, 'year' => $year],
                        [
                            'entitled' => $e->days_entitled,
                            'used' => 0,
                            'adjusted' => 0,
                            'balance' => $e->days_entitled,
                        ]
                    );
                }
            }
        }

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        StaffProfile::findOrFail($id)->delete();

        return response()->noContent();
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $staff = StaffProfile::findOrFail($id);
        $user = User::where('email', $staff->email)->first();
        if (! $user) {
            return response()->json(['error' => 'User account not found'], 404);
        }

        $password = bin2hex(random_bytes(4));
        $user->update(['password' => password_hash($password, PASSWORD_BCRYPT)]);

        return response()->json(['message' => 'Password reset successful', 'generated_password' => $password]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $staff = StaffProfile::findOrFail($id);
        $status = $data['status'] ?? '';

        if (! in_array($status, ['active', 'resigned', 'terminated', 'archived'])) {
            return response()->json(['error' => 'Invalid status. Must be active, resigned, terminated, or archived.'], 422);
        }

        if ($status === 'active') {
            $staff->update([
                'is_active' => true,
                'last_day' => null,
                'archive_reason' => null,
            ]);
        } else {
            $lastDay = $data['last_day'] ?? date('Y-m-d');
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $lastDay)) {
                return response()->json(['error' => 'last_day must be a valid date (YYYY-MM-DD)'], 422);
            }

            $notes = trim($data['notes'] ?? '');
            $archiveReason = $status.($notes ? ': '.$notes : '');

            $staff->update([
                'is_active' => false,
                'last_day' => $lastDay,
                'archive_reason' => $archiveReason,
            ]);
        }

        return response()->json($staff);
    }

    public function myProjects(Request $request): JsonResponse
    {
        $user = $request->user();
        $staff = StaffProfile::where('email', $user->email)->first();

        if (! $staff) {
            return response()->json(['error' => 'Staff profile not found'], 404);
        }

        $projects = $staff->projects()->with('clientPic')->get();

        return response()->json(['data' => $projects]);
    }

    public function myTasks(Request $request): JsonResponse
    {
        $user = $request->user();
        $staff = StaffProfile::where('email', $user->email)->first();

        if (! $staff) {
            return response()->json(['error' => 'Staff profile not found'], 404);
        }

        $tasks = Task::with(['phase.project', 'staff' => fn ($q) => $q->select('id', 'name')])
            ->where(function ($q) use ($staff) {
                $q->where('assigned_to', (string) $staff->id)
                    ->orWhereHas('staff', fn ($q) => $q->where('staff_id', $staff->id))
                    ->orWhereHas('phase.staff', fn ($q) => $q->where('staff_id', $staff->id));
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $tasks]);
    }

    public function projectPhases(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $staff = StaffProfile::where('email', $user->email)->first();

        if (! $staff) {
            return response()->json(['error' => 'Staff profile not found'], 404);
        }

        $project = Project::with([
            'clientRelation:id,company_name',
            'clientPic:id,name',
            'projectManager:id,name',
        ])->findOrFail($id);

        $phases = Phase::with([
            'startedBy:id,name',
            'completedBy:id,name',
            'documents',
            'tasks' => fn ($q) => $q->orderBy('created_at'),
            'tasks.staff' => fn ($q) => $q->select('id', 'name'),
            'tasks.documents',
            'tasks.startedBy:id,name',
            'tasks.pausedBy:id,name',
            'tasks.completedBy:id,name',
            'comments.staff:id,name',
            'staff' => fn ($q) => $q->select('id', 'name'),
        ])
            ->where('project_id', $id)
            ->orderBy('order')
            ->get();

        return response()->json([
            'project' => $project,
            'phases' => $phases,
        ]);
    }

    public function resign(Request $request, int $id): JsonResponse
    {
        $staff = StaffProfile::findOrFail($id);
        $data = $request->all();
        $lastDay = $data['last_day'] ?? date('Y-m-d');

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $lastDay)) {
            return response()->json(['error' => 'last_day must be a valid date (YYYY-MM-DD)'], 422);
        }

        $notes = trim($data['notes'] ?? '');
        $archiveReason = 'resigned'.($notes ? ': '.$notes : '');

        $staff->update([
            'is_active' => false,
            'last_day' => $lastDay,
            'archive_reason' => $archiveReason,
        ]);

        return response()->json($staff);
    }

    public function myProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $staff = StaffProfile::where('email', $user->email)->first();

        if (! $staff) {
            return response()->json(['error' => 'Staff profile not found'], 404);
        }

        $staff->load('leaveGroup');

        return response()->json($staff);
    }
}
