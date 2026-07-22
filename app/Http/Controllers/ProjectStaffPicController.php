<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\StaffProfile;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectStaffPicController extends Controller
{
    public function index(Request $request, int $id): JsonResponse
    {
        $project = Project::with('staffPics')->findOrFail($id);

        return response()->json($project->staffPics);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $data = $request->all();
        $staffIds = $data['staff_ids'] ?? [];

        if (empty($staffIds)) {
            return response()->json(['error' => 'staff_ids array is required'], 422);
        }

        $project->staffPics()->syncWithoutDetaching($staffIds);
        $project->load('staffPics');

        try {
            $assignedStaff = StaffProfile::whereIn('id', $staffIds)->get();
            $recipients = $assignedStaff->filter(fn ($s) => ! empty($s->email))
                ->map(fn ($s) => ['email' => $s->email, 'name' => $s->name])
                ->values()->toArray();
            if (! empty($recipients)) {
                NotificationService::queueToMany(
                    NotificationEvent::PROJECT_ASSIGNED,
                    $recipients,
                    ['project_name' => $project->name],
                    'App\\Models\\Project',
                    $project->id,
                    'Assigned to Project: '.$project->name,
                    'You have been assigned as PIC for project '.$project->name.'.',
                    '/projects/'.$project->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: project.assigned', ['project_id' => $project->id, 'error' => $e->getMessage()]);
        }

        return response()->json($project->staffPics);
    }

    public function destroy(Request $request, int $id, int $staffId): JsonResponse
    {
        $project = Project::findOrFail($id);
        $project->staffPics()->detach($staffId);

        return response()->noContent();
    }
}
