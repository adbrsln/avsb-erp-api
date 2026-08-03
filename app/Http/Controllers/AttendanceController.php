<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Geofence;
use App\Models\Project;
use App\Models\StaffProfile;
use App\Services\FileStorageService;
use App\Services\GeofenceService;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use App\Traits\PaginatedResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AttendanceController extends Controller
{
    use PaginatedResponse;

    private FileStorageService $storage;

    public function __construct()
    {
        $this->storage = new FileStorageService;
    }

    private function getStaffId(Request $request): ?int
    {
        $user = $request->user();
        if (! $user || ! $user->email) {
            return null;
        }

        $staff = StaffProfile::where('email', $user->email)->first();

        return $staff ? (int) $staff->id : null;
    }

    private function isPmPlus(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        return (bool) array_intersect($user->getRoleNames(), ['admin', 'pm', 'super_admin']);
    }

    private function isHrPlus(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        return (bool) array_intersect($user->getRoleNames(), ['admin', 'hr', 'super_admin']);
    }

    private function canViewSummary(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        return (bool) array_intersect($user->getRoleNames(), ['admin', 'pm', 'hr', 'finance', 'super_admin']);
    }

    private function validateCoords(mixed $lat, mixed $lng): ?string
    {
        if ($lat === null && $lng === null) {
            return null;
        }

        if ($lat === null || $lng === null) {
            return 'Both latitude and longitude are required together.';
        }

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return 'Latitude and longitude must be numeric.';
        }

        $latValue = (float) $lat;
        $lngValue = (float) $lng;

        if ($latValue < -90 || $latValue > 90) {
            return 'Latitude must be between -90 and 90.';
        }

        if ($lngValue < -180 || $lngValue > 180) {
            return 'Longitude must be between -180 and 180.';
        }

        return null;
    }

    /**
     * Validates GPS accuracy and returns the containing active geofence, or a 422 response.
     * When geofence enforcement is disabled (company setting), returns null — punch allowed
     * from any valid GPS location.
     */
    private function enforceGeofence(mixed $lat, mixed $lng, mixed $accuracy): Geofence|JsonResponse|null
    {
        if (CompanySetting::value('geofence_enforced', true) === false) {
            return null;
        }

        if ($lat === null || $lng === null) {
            return null;
        }

        $accuracyError = GeofenceService::accuracyError($accuracy);
        if ($accuracyError) {
            return response()->json(['error' => $accuracyError], 422);
        }

        $geofence = GeofenceService::findContaining((float) $lat, (float) $lng);
        if ($geofence) {
            return $geofence;
        }

        $hasActive = Geofence::where('is_active', true)->exists();

        return response()->json([
            'error' => $hasActive
                ? 'Location is outside all geofenced sites.'
                : 'No active geofenced sites are configured. Contact an administrator.',
        ], 422);
    }

    /**
     * Returns a schedule-flag reason when the punch falls outside the staff's
     * allocated working window (±15 min grace), or null when it does not apply.
     *
     * Window resolution: staff override → company default. No window configured,
     * or part-time worker → null (no enforcement).
     */
    private function scheduleFlagReason(StaffProfile $staff, Carbon $punchTime, string $type): ?string
    {
        if ($staff->worker_status === 'part_time') {
            return null;
        }

        $start = $staff->work_start_time ?: CompanySetting::value('work_start_time');
        $end = $staff->work_end_time ?: CompanySetting::value('work_end_time');

        if (! $start || ! $end) {
            return null;
        }

        $graceMinutes = 15;
        $windowStart = strtotime($start);
        $windowEnd = strtotime($end);
        $startMin = ((int) date('G', $windowStart)) * 60 + (int) date('i', $windowStart) - $graceMinutes;
        $endMin = ((int) date('G', $windowEnd)) * 60 + (int) date('i', $windowEnd) + $graceMinutes;
        $punchMinutes = ((int) $punchTime->format('G')) * 60 + (int) $punchTime->format('i');

        if ($endMin <= $startMin) {
            // Overnight shift: window crosses midnight
            $inWindow = $punchMinutes >= $startMin || $punchMinutes <= $endMin;
        } else {
            $inWindow = $punchMinutes >= $startMin && $punchMinutes <= $endMin;
        }

        if ($inWindow) {
            return null;
        }

        return ucfirst($type).' outside scheduled window ('.date('H:i', $windowStart).'–'.date('H:i', $windowEnd).').';
    }

    public function clockIn(Request $request): JsonResponse
    {
        $user = $request->user();
        $email = $user->email ?? '';
        $staff = StaffProfile::where('email', $email)->first();
        $staffId = $staff ? (int) $staff->id : null;

        $body = $request->all();
        if (! empty($body['staff_id'])) {
            $userRoles = $user ? $user->getRoleNames() : [];
            if (! array_intersect($userRoles, ['admin', 'pm', 'super_admin'])) {
                return response()->json(['error' => 'Only admins and PMs can clock in on behalf of other staff'], 403);
            }
            $staffId = (int) $body['staff_id'];
        }

        if (! $staffId || ! StaffProfile::find($staffId)) {
            return response()->json(['error' => 'Staff not found'], 404);
        }

        $staffProfile = StaffProfile::find($staffId);
        $projectId = ! empty($body['project_id']) ? (int) $body['project_id'] : null;

        if ($staffProfile->worker_status === 'part_time' && ! $projectId) {
            return response()->json(['error' => 'Project selection is required for part-time clock-in'], 422);
        }

        if ($projectId) {
            $project = Project::find($projectId);
            if (! $project) {
                return response()->json(['error' => 'Project not found'], 404);
            }
        }

        $now = Carbon::now();
        $today = date('Y-m-d');

        $activeSession = Attendance::where('staff_id', $staffId)
            ->whereNull('clock_out')
            ->where('date', '<', $today)
            ->first();

        if ($activeSession) {
            $hours = round($activeSession->clock_in->diffInMinutes($now, true) / 60, 2);
            $activeSession->update([
                'clock_out' => $now,
                'total_hours' => $hours,
                'clock_out_ip' => $request->ip(),
            ]);
        }

        $existing = Attendance::where('staff_id', $staffId)->where('date', $today)->latest()->first();

        if ($existing && ! $existing->clock_out) {
            return response()->json(['error' => 'Already clocked in today', 'attendance' => $existing->toArray()], 422);
        }

        $lat = $body['latitude'] ?? null;
        $lng = $body['longitude'] ?? null;

        $coordError = $this->validateCoords($lat, $lng);
        if ($coordError) {
            return response()->json(['error' => $coordError], 422);
        }

        $accuracy = $body['accuracy'] ?? null;
        $geofenceResult = $this->enforceGeofence($lat, $lng, $accuracy);
        if ($geofenceResult instanceof JsonResponse) {
            return $geofenceResult;
        }
        $geofence = $geofenceResult;

        $photo = $request->file('photo');
        if (! $photo || ! $photo->isValid()) {
            return response()->json(['error' => 'A site photo is required to clock in.'], 422);
        }

        $error = FileStorageService::validateUpload($photo);
        if ($error) {
            return response()->json(['error' => $error], 422);
        }

        $ext = pathinfo($photo->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'punch_'.$staffId.'_'.time().'.'.$ext;
        $photoPath = 'uploads/attendance/'.date('Y').'/'.date('m').'/'.$filename;
        $this->storage->put($photoPath, file_get_contents($photo->getPathname()), $photo->getClientMimeType());

        $scheduleReason = $this->scheduleFlagReason($staffProfile, $now, 'clock-in');

        // Always create a new record — supports multiple punches per day
        $record = Attendance::create([
            'staff_id' => $staffId,
            'date' => $today,
            'clock_in' => $now,
            'clock_in_latitude' => $lat,
            'clock_in_longitude' => $lng,
            'clock_in_photo' => $photoPath,
            'clock_in_ip' => $request->ip(),
            'status' => 'present',
            'project_id' => $projectId,
            'geofence_id' => $geofence ? $geofence->id : null,
            'schedule_flagged' => $scheduleReason !== null,
            'schedule_flag_reason' => $scheduleReason,
        ]);

        return response()->json($record->load('geofence:id,name')->toArray(), 201);
    }

    public function clockOut(Request $request, int $id): JsonResponse
    {
        $record = Attendance::find($id);
        if (! $record) {
            return response()->json(['error' => 'Attendance record not found'], 404);
        }

        $staffId = $this->getStaffId($request);
        if (! $staffId || $record->staff_id !== $staffId) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ($record->clock_out) {
            return response()->json(['error' => 'Already clocked out'], 422);
        }

        $now = Carbon::now();

        $body = $request->all();
        $outLat = $body['latitude'] ?? null;
        $outLng = $body['longitude'] ?? null;

        $coordError = $this->validateCoords($outLat, $outLng);
        if ($coordError) {
            return response()->json(['error' => $coordError], 422);
        }

        $accuracy = $body['accuracy'] ?? null;
        $geofenceResult = $this->enforceGeofence($outLat, $outLng, $accuracy);
        if ($geofenceResult instanceof JsonResponse) {
            return $geofenceResult;
        }
        $geofence = $geofenceResult;

        $photo = $request->file('photo');
        if (! $photo || ! $photo->isValid()) {
            return response()->json(['error' => 'A site photo is required to clock out.'], 422);
        }

        $photoError = FileStorageService::validateUpload($photo);
        if ($photoError) {
            return response()->json(['error' => $photoError], 422);
        }

        $ext = pathinfo($photo->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'punch_out_'.$record->staff_id.'_'.time().'.'.$ext;
        $photoOutPath = 'uploads/attendance/'.date('Y').'/'.date('m').'/'.$filename;
        $this->storage->put($photoOutPath, file_get_contents($photo->getPathname()), $photo->getClientMimeType());

        $totalHours = round($record->clock_in->diffInMinutes($now, true) / 60, 2);
        $flagged = false;

        if ($totalHours > 14) {
            $flagged = true;
        }

        $staffProfile = StaffProfile::find($record->staff_id);
        $scheduleReason = $staffProfile ? $this->scheduleFlagReason($staffProfile, $now, 'clock-out') : null;

        $record->update([
            'clock_out' => $now,
            'total_hours' => $totalHours,
            'clock_out_photo' => $photoOutPath,
            'clock_out_latitude' => $outLat,
            'clock_out_longitude' => $outLng,
            'clock_out_ip' => $request->ip(),
            'clock_out_geofence_id' => $geofence ? $geofence->id : null,
            'flagged' => $flagged,
            'flagged_reason' => $flagged ? "Shift of {$totalHours}h exceeds 14h limit." : null,
            'schedule_flagged' => (bool) $record->schedule_flagged || $scheduleReason !== null,
            'schedule_flag_reason' => $scheduleReason ?? $record->schedule_flag_reason,
        ]);

        if ($flagged) {
            try {
                $staff = StaffProfile::find($record->staff_id);
                $hrRecipients = NotificationRecipientResolver::getHr();
                $allRecipients = $hrRecipients;
                if ($staff) {
                    $allRecipients[] = ['email' => $staff->email, 'name' => $staff->name];
                }
                NotificationService::queueToMany(
                    NotificationEvent::ATTENDANCE_FLAGGED,
                    $allRecipients,
                    [
                        'staff_name' => $staff?->name ?? 'Unknown',
                        'date' => $record->date ?? '',
                        'url' => '/attendance',
                    ],
                    'App\\Models\\Attendance',
                    $record->id
                );
            } catch (\Throwable $e) {
                logger()->error('Notification failed: attendance.flagged', ['attendance_id' => $record->id, 'error' => $e->getMessage()]);
            }
        }

        $record->refresh();

        return response()->json($record->load('geofence:id,name', 'clockOutGeofence:id,name')->toArray());
    }

    public function clearFlag(Request $request, int $id): JsonResponse
    {
        if (! $this->isPmPlus($request) && ! $this->isHrPlus($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $record = Attendance::find($id);
        if (! $record) {
            return response()->json(['error' => 'Attendance record not found'], 404);
        }

        if (! $record->flagged && ! $record->schedule_flagged) {
            return response()->json(['error' => 'Record is not flagged'], 422);
        }

        $user = $request->user();
        $staff = $user->email ? StaffProfile::where('email', $user->email)->first() : null;

        $record->update([
            'flagged' => false,
            'flagged_reason' => null,
            'flagged_cleared_by' => $staff ? $staff->id : null,
            'flagged_cleared_at' => Carbon::now(),
            'schedule_flagged' => false,
            'schedule_flag_reason' => null,
        ]);

        return response()->json($record->toArray());
    }

    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $email = $user->email ?? '';
        $staff = StaffProfile::where('email', $email)->first();

        if (! $staff) {
            return response()->json(['attendance' => null, 'staff' => null]);
        }

        $today = Attendance::with('project:id,name', 'geofence:id,name', 'clockOutGeofence:id,name')->where('staff_id', $staff->id)->where('date', date('Y-m-d'))->latest()->first();

        return response()->json([
            'attendance' => $today ? $today->toArray() : null,
            'staff' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'hourly_rate' => $staff->hourly_rate,
                'worker_status' => $staff->worker_status,
            ],
        ]);
    }

    public function myProjects(Request $request): JsonResponse
    {
        $user = $request->user();
        $email = $user->email ?? '';
        $staff = StaffProfile::where('email', $email)->first();

        if (! $staff) {
            return response()->json([]);
        }

        $projects = $staff->projects()
            ->whereIn('status', ['active', 'planning'])
            ->select(['id', 'name', 'project_code', 'client'])
            ->orderBy('name')
            ->get();

        return response()->json($projects);
    }

    public function records(Request $request): JsonResponse
    {
        if (! $this->isPmPlus($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $params = $request->query();
        $query = Attendance::with('staff')
            ->whereDoesntHave('staff.user.roles', fn ($q) => $q->where('role', 'super_admin'));

        if (! empty($params['staff_id'])) {
            $query->where('staff_id', (int) $params['staff_id']);
        }
        if (! empty($params['date_from'])) {
            $query->whereDate('date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('date', '<=', $params['date_to']);
        }

        $query->orderByDesc('date')->orderByDesc('clock_in');

        if (! empty($params['all']) && $params['all'] === 'true') {
            return response()->json(['data' => $query->get()]);
        }

        return $this->paginate($query, $params);
    }

    public function exportCsv(Request $request): Response|JsonResponse
    {
        if (! $this->isPmPlus($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $params = $request->query();
        $query = Attendance::with('staff')
            ->whereDoesntHave('staff.user.roles', fn ($q) => $q->where('role', 'super_admin'));

        if (! empty($params['staff_id'])) {
            $query->where('staff_id', (int) $params['staff_id']);
        }
        if (! empty($params['date_from'])) {
            $query->whereDate('date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('date', '<=', $params['date_to']);
        }

        $records = $query->orderBy('date')->orderBy('staff_id')->get();

        $filename = 'attendance-export-'.($params['date_from'] ?? date('Y-m-01')).'.csv';

        $handle = fopen('php://temp', 'w+');
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'Staff Name', 'Employee ID', 'Department', 'Date',
            'Clock In', 'Clock Out', 'Total Hours', 'Status',
            'Clock-In Lat', 'Clock-In Lng', 'Clock-Out Lat', 'Clock-Out Lng',
            'Flagged', 'Flagged Reason', 'Schedule Flagged', 'Schedule Flag Reason',
        ]);

        foreach ($records as $r) {
            $staff = $r->staff;
            fputcsv($handle, [
                $staff->name ?? '',
                $staff->employee_id ?? '',
                $staff->department ?? '',
                $r->date,
                $r->clock_in ?? '',
                $r->clock_out ?? '',
                number_format((float) $r->total_hours, 2),
                $r->status ?? '',
                $r->clock_in_latitude ?? '',
                $r->clock_in_longitude ?? '',
                $r->clock_out_latitude ?? '',
                $r->clock_out_longitude ?? '',
                $r->flagged ? 'Yes' : 'No',
                $r->flagged_reason ?? '',
                $r->schedule_flagged ? 'Yes' : 'No',
                $r->schedule_flag_reason ?? '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => strlen($csv),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        if (! $this->canViewSummary($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $params = $request->query();
        $staffId = ! empty($params['staff_id']) ? (int) $params['staff_id'] : null;
        $dateFrom = $params['date_from'] ?? date('Y-m-01');
        $dateTo = $params['date_to'] ?? date('Y-m-t');

        $query = Attendance::whereDate('date', '>=', $dateFrom)->whereDate('date', '<=', $dateTo)
            ->whereDoesntHave('staff.user.roles', fn ($q) => $q->where('role', 'super_admin'));
        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        $records = $query->get();
        $totalHours = $records->sum('total_hours');
        $totalDays = $records->pluck('date')->unique()->count();

        $byStaff = $records->groupBy('staff_id')->map(function ($items, $sid) {
            $staff = StaffProfile::find($sid);
            $hours = $items->sum('total_hours');

            return [
                'staff_id' => (int) $sid,
                'name' => $staff->name ?? 'Unknown',
                'hourly_rate' => $staff->hourly_rate ?? 0,
                'total_hours' => round($hours, 2),
                'total_days' => $items->pluck('date')->unique()->count(),
                'gross_pay' => round($hours * ($staff->hourly_rate ?? 0), 2),
            ];
        })->values();

        return response()->json([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_hours' => round($totalHours, 2),
            'total_days' => $totalDays,
            'by_staff' => $byStaff,
        ]);
    }

    public function servePhoto(Request $request, int $id, string $type): mixed
    {
        $record = Attendance::find($id);
        if (! $record) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        $user = $request->user();
        $userRoles = $user ? $user->getRoleNames() : [];
        $isAdmin = (bool) array_intersect($userRoles, ['admin', 'hr', 'super_admin']);
        if (! $isAdmin) {
            $staff = $user->email ? StaffProfile::where('email', $user->email)->first() : null;
            if (! $staff || $record->staff_id !== $staff->id) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        }

        $path = $type === 'out' ? $record->clock_out_photo : $record->clock_in_photo;
        if (! $path) {
            return response()->json(['error' => 'Photo not found'], 404);
        }

        if (! $this->storage->exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $params = $request->query();
        if (isset($params['presign']) && $params['presign'] === '1') {
            $url = $this->storage->getPresignedUrl($path);
            if ($url) {
                return response()->json(['url' => $url, 'filename' => basename($path)]);
            }
        }

        $contents = $this->storage->get($path);
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return response($contents, 200, [
            'Content-Type' => $ext === 'png' ? 'image/png' : 'image/jpeg',
            'Content-Length' => $this->storage->size($path),
            'Content-Disposition' => 'inline; filename="photo.'.$ext.'"',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
