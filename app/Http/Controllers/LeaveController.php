<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\StaffLeaveBalance;
use App\Models\StaffProfile;
use App\Services\FileStorageService;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    use PaginatedResponse;

    private const VALID_TYPES = ['annual', 'medical', 'unpaid', 'emergency', 'maternity', 'paternity', 'marriage', 'compassionate'];

    private const TYPE_MAX_DAYS = [
        'annual' => 30,
        'medical' => 22,
        'maternity' => 98,
        'paternity' => 7,
        'marriage' => 7,
        'compassionate' => 5,
        'emergency' => 3,
        'unpaid' => null,
    ];

    private const GENDER_TYPES = [
        'male' => ['annual', 'medical', 'unpaid', 'emergency', 'paternity', 'marriage', 'compassionate'],
        'female' => ['annual', 'medical', 'unpaid', 'emergency', 'maternity', 'marriage', 'compassionate'],
    ];

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = LeaveApplication::with('staff', 'approver');
        if (isset($params['staff_id'])) {
            $query->where('staff_id', $params['staff_id']);
        }
        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (! empty($params['type'])) {
            $query->where('type', $params['type']);
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
        if (empty($data['type'])) {
            $errors[] = 'type is required';
        }
        if (empty($data['start_date'])) {
            $errors[] = 'start_date is required';
        }
        if (empty($data['end_date'])) {
            $errors[] = 'end_date is required';
        }

        if (! empty($data['type']) && ! in_array($data['type'], self::VALID_TYPES)) {
            $errors[] = 'type must be one of: '.implode(', ', self::VALID_TYPES);
        }

        if (! empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        $staffId = (int) $data['staff_id'];
        $type = $data['type'];
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];
        $isHalfDay = ! empty($data['is_half_day']);

        if ($endDate < $startDate) {
            return response()->json(['errors' => ['end_date must be on or after start_date']], 422);
        }

        $staff = StaffProfile::find($staffId);
        if ($staff && $staff->gender) {
            $allowedTypes = self::GENDER_TYPES[$staff->gender] ?? self::VALID_TYPES;
            if (! in_array($type, $allowedTypes)) {
                return response()->json(['errors' => ["{$type} leave is not available for {$staff->gender} staff"]], 422);
            }
        }

        if ($isHalfDay) {
            if (! LeaveApplication::halfDayAllowed($type)) {
                $errors[] = "Half-day leave is not allowed for {$type} leave";
            }
            if ($endDate !== $startDate) {
                $errors[] = 'Half-day leave must be a single day';
            }
        }

        $maxDays = self::TYPE_MAX_DAYS[$type] ?? null;
        $requestedDays = LeaveApplication::workingDaysCount(Carbon::parse($startDate), Carbon::parse($endDate), $isHalfDay);
        if ($maxDays !== null && $requestedDays > $maxDays) {
            $errors[] = "Maximum {$maxDays} working days allowed for {$type} leave (requested: {$requestedDays})";
        }

        $overlap = LeaveApplication::overlapping($staffId, $startDate, $endDate)->exists();
        if ($overlap) {
            $errors[] = 'You already have a leave application overlapping with these dates';
        }

        $year = Carbon::parse($startDate)->year;
        $balance = StaffLeaveBalance::where('staff_id', $staffId)
            ->where('type', $type)
            ->where('year', $year)
            ->first();

        if ($balance && $balance->balance < $requestedDays && $type !== 'unpaid') {
            $errors[] = "Insufficient {$type} leave balance. Available: {$balance->balance}, Requested: {$requestedDays}";
        }

        if (! empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        $mcPath = null;
        if ($type === 'medical') {
            $mcFile = $request->file('mc_document');
            if ($mcFile && $mcFile->isValid()) {
                $error = FileStorageService::validateUpload($mcFile);
                if ($error) {
                    return response()->json(['error' => $error], 422);
                }
                $ext = strtolower(pathinfo($mcFile->getClientOriginalName(), PATHINFO_EXTENSION));
                $filename = 'mc-'.$staffId.'-'.time().'.'.$ext;
                $mcPath = 'leave-mc/'.$filename;
                $storage = new FileStorageService;
                $storage->put($mcPath, file_get_contents($mcFile->getPathname()), $mcFile->getClientMimeType());
            }
        }

        $data['status'] = 'pending';
        $data['leave_ref'] = (new NumberingService)->generate('leave');
        $data['mc_document_path'] = $mcPath;
        $item = LeaveApplication::create(fillableData(new LeaveApplication, $data));
        $item->load('staff', 'approver');

        try {
            $recipients = NotificationRecipientResolver::getApprovers('leave');
            $staff = $item->staff()->first();
            NotificationService::queueToMany(
                NotificationEvent::LEAVE_APPLIED,
                $recipients,
                [
                    'staff_name' => $staff?->name ?? 'Unknown',
                    'leave_type' => $item->type,
                    'date_range' => $item->start_date->format('Y-m-d').' to '.$item->end_date->format('Y-m-d'),
                    'days' => $item->days,
                    'url' => '/approvals?type=leave',
                ],
                'App\\Models\\LeaveApplication',
                $item->id
            );
        } catch (\Throwable $e) {
            logger()->error('Notification failed: leave.applied', ['leave_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = LeaveApplication::with('staff', 'approver')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = LeaveApplication::findOrFail($id);
        if (! $this->canModifyLeave($request, $item)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->all();

        $user = $request->user();
        $userRoles = $user ? $user->getRoleNames() : [];
        $isAdmin = (bool) array_intersect($userRoles, ['admin', 'hr', 'super_admin']);

        if ($isAdmin) {
            $item->update(fillableData($item, $data));
        } else {
            if ($item->status !== 'pending') {
                return response()->json(['error' => 'Only pending leaves can be edited'], 422);
            }
            unset($data['status'], $data['approver_id'], $data['approved_at'], $data['rejection_reason']);
            $item->update(fillableData($item, $data));
        }

        $item->load('staff', 'approver');

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = LeaveApplication::findOrFail($id);
        if (! $this->canModifyLeave($request, $item)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ($item->status === 'approved') {
            $year = $item->start_date->year;
            $balance = StaffLeaveBalance::where('staff_id', $item->staff_id)
                ->where('type', $item->type)
                ->where('year', $year)
                ->first();
            if ($balance) {
                $balance->update([
                    'used' => max(0, $balance->used - $item->days),
                    'balance' => $balance->entitled - max(0, $balance->used - $item->days) + $balance->adjusted,
                ]);
            }
        }

        $item->delete();

        return response()->json(null, 204);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $item = LeaveApplication::with('staff')->findOrFail($id);
        if ($item->status !== 'pending') {
            return response()->json(['errors' => ['Leave is already '.$item->status]], 422);
        }

        $user = $request->user();
        $email = $user->email ?? '';
        $approver = $email ? StaffProfile::where('email', $email)->first() : null;

        $year = $item->start_date->year;
        $balance = StaffLeaveBalance::where('staff_id', $item->staff_id)
            ->where('type', $item->type)
            ->where('year', $year)
            ->first();

        $days = $item->days;

        if ($balance && $balance->balance < $days && $item->type !== 'unpaid') {
            return response()->json([
                'errors' => ["Insufficient balance. Available: {$balance->balance}, Required: {$days}"],
            ], 422);
        }

        $item->update([
            'status' => 'approved',
            'approver_id' => $approver ? $approver->id : null,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        if ($balance) {
            $balance->update([
                'used' => $balance->used + $days,
                'balance' => $balance->entitled - ($balance->used + $days) + $balance->adjusted,
            ]);
        }

        try {
            $staff = $item->staff()->first();
            if ($staff) {
                NotificationService::queue(
                    NotificationEvent::LEAVE_APPROVED,
                    $staff->email,
                    $staff->name,
                    [
                        'leave_type' => $item->type,
                        'date_range' => $item->start_date->format('Y-m-d').' to '.$item->end_date->format('Y-m-d'),
                        'days' => $days,
                        'url' => '/leaves',
                    ],
                    'App\\Models\\LeaveApplication',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: leave.approved', ['leave_id' => $item->id, 'error' => $e->getMessage()]);
        }

        $item->load('staff', 'approver');

        return response()->json($item);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $item = LeaveApplication::findOrFail($id);
        if ($item->status !== 'pending') {
            return response()->json(['errors' => ['Leave is already '.$item->status]], 422);
        }
        if (empty($data['rejection_reason'])) {
            return response()->json(['errors' => ['rejection_reason is required to reject']], 422);
        }
        $item->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
        ]);

        try {
            $staff = $item->staff()->first();
            if ($staff) {
                NotificationService::queue(
                    NotificationEvent::LEAVE_REJECTED,
                    $staff->email,
                    $staff->name,
                    [
                        'leave_type' => $item->type,
                        'date_range' => $item->start_date->format('Y-m-d').' to '.$item->end_date->format('Y-m-d'),
                        'days' => $item->days,
                        'reason' => $item->rejection_reason ?? '',
                        'url' => '/leaves',
                    ],
                    'App\\Models\\LeaveApplication',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: leave.rejected', ['leave_id' => $item->id, 'error' => $e->getMessage()]);
        }

        $item->load('staff', 'approver');

        return response()->json($item);
    }

    public function serveMcDocument(Request $request, int $id): JsonResponse
    {
        $item = LeaveApplication::with('staff')->findOrFail($id);

        $user = $request->user();
        $userRoles = $user ? $user->getRoleNames() : [];
        $isAdmin = (bool) array_intersect($userRoles, ['admin', 'hr', 'super_admin']);
        if (! $isAdmin) {
            $staff = $user->email ? StaffProfile::where('email', $user->email)->first() : null;
            if (! $staff || $item->staff_id !== $staff->id) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        }

        if (! $item->mc_document_path) {
            return response()->json(['error' => 'No medical certificate uploaded'], 404);
        }
        $storage = new FileStorageService;
        if (! $storage->exists($item->mc_document_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }
        $params = $request->query();
        if (isset($params['presign']) && $params['presign'] === '1') {
            $filename = basename($item->mc_document_path);
            $url = $storage->getPresignedUrl($item->mc_document_path, 5, $filename);
            if ($url) {
                return response()->json(['url' => $url, 'filename' => $filename]);
            }
        }
        $contents = $storage->get($item->mc_document_path);
        $ext = strtolower(pathinfo($item->mc_document_path, PATHINFO_EXTENSION));
        $mimeTypes = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';

        return response($contents, 200, [
            'Content-Type' => $mime,
            'Content-Length' => $storage->size($item->mc_document_path),
            'Content-Disposition' => 'inline; filename="mc-'.$item->id.'.'.$ext.'"',
        ]);
    }

    private function canModifyLeave(Request $request, LeaveApplication $leave): bool
    {
        $user = $request->user();
        $userRoles = $user ? $user->getRoleNames() : [];
        $isAdmin = (bool) array_intersect($userRoles, ['admin', 'hr', 'super_admin']);
        if ($isAdmin) {
            return true;
        }

        $email = $user->email ?? '';
        if (empty($email)) {
            return false;
        }

        $staff = StaffProfile::where('email', $email)->first();

        return $staff && $leave->staff_id === $staff->id;
    }
}
