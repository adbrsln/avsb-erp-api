<?php

namespace App\Http\Controllers;

use App\Models\StaffProfile;
use App\Models\Timecard;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimecardController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = Timecard::query();
        if (isset($params['staff_id'])) {
            $query->where('staff_id', $params['staff_id']);
        }

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();
        $errors = [];
        if (empty($data['staff_id'])) {
            $errors[] = 'staff_id is required';
        }
        if (empty($data['date'])) {
            $errors[] = 'date is required';
        }
        if (! isset($data['hours_worked']) || $data['hours_worked'] < 1 || $data['hours_worked'] > 24) {
            $errors[] = 'hours_worked must be between 1 and 24';
        }
        if (! empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }
        $data['status'] = 'pending';
        $item = Timecard::create(fillableData(new Timecard, $data));

        try {
            $staff = StaffProfile::find($data['staff_id'] ?? 0);
            $recipients = NotificationRecipientResolver::getApprovers('timecard');
            NotificationService::queueToMany(
                NotificationEvent::TIMECARD_SUBMITTED,
                $recipients,
                [
                    'staff_name' => $staff?->name ?? 'Unknown',
                    'date' => $data['date'] ?? '',
                    'hours' => $data['hours_worked'] ?? 0,
                    'url' => '/approvals?type=timecard',
                ],
                'App\\Models\\Timecard',
                $item->id
            );
        } catch (\Throwable $e) {
            logger()->error('Notification failed: timecard.submitted', ['timecard_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = Timecard::findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = Timecard::findOrFail($id);
        $staff = $this->resolveStaff($request);
        if (! $staff || ($item->staff_id !== $staff->id && ! $this->isAdmin($request))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $item->update(fillableData($item, $request->all()));

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = Timecard::findOrFail($id);
        $staff = $this->resolveStaff($request);
        if (! $staff || ($item->staff_id !== $staff->id && ! $this->isAdmin($request))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $item->delete();

        return response()->json(null, 204);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $item = Timecard::findOrFail($id);
        $item->update(['status' => 'approved']);

        try {
            $staff = StaffProfile::find($item->staff_id);
            if ($staff) {
                NotificationService::queue(
                    NotificationEvent::TIMECARD_APPROVED,
                    $staff->email,
                    $staff->name,
                    [
                        'date' => $item->date ?? '',
                        'url' => '/attendance',
                    ],
                    'App\\Models\\Timecard',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: timecard.approved', ['timecard_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $item = Timecard::findOrFail($id);
        $item->update(['status' => 'rejected']);

        try {
            $staff = StaffProfile::find($item->staff_id);
            if ($staff) {
                NotificationService::queue(
                    NotificationEvent::TIMECARD_REJECTED,
                    $staff->email,
                    $staff->name,
                    [
                        'date' => $item->date ?? '',
                        'url' => '/attendance',
                    ],
                    'App\\Models\\Timecard',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: timecard.rejected', ['timecard_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    private function resolveStaff(Request $request): ?StaffProfile
    {
        $user = $request->user();
        $email = $user->email ?? '';

        return $email ? StaffProfile::where('email', $email)->first() : null;
    }

    private function isAdmin(Request $request): bool
    {
        $user = $request->user();
        $roles = $user ? $user->getRoleNames() : [];

        return (bool) array_intersect($roles, ['admin', 'hr', 'super_admin']);
    }
}
