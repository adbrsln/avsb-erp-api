<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = ActivityLog::with('causer')->orderBy('created_at', 'desc');

        if (! empty($params['event'])) {
            $query->where('event', $params['event']);
        }
        if (! empty($params['log_name'])) {
            $query->where('log_name', $params['log_name']);
        }
        if (! empty($params['subject_type'])) {
            $query->where('subject_type', $params['subject_type']);
        }
        if (! empty($params['date_from'])) {
            $query->where('created_at', '>=', $params['date_from'].' 00:00:00');
        }
        if (! empty($params['date_to'])) {
            $query->where('created_at', '<=', $params['date_to'].' 23:59:59');
        }
        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                    ->orWhere('log_name', 'like', "%{$s}%");
            });
        }

        return $this->paginate($query, $params);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $log = ActivityLog::with('causer')->findOrFail($id);

        return response()->json($log);
    }

    public function projectLog(Request $request, int $id): JsonResponse
    {
        $params = $request->query();
        $projectId = $id;
        $query = ActivityLog::with('causer')
            ->where('project_id', $projectId)
            ->orderBy('created_at', 'desc');

        return $this->paginate($query, $params);
    }
}
