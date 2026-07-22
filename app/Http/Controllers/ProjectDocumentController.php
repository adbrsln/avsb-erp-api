<?php

namespace App\Http\Controllers;

use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\StaffProfile;
use App\Models\Task;
use App\Services\FileStorageService;
use App\Traits\NotifiesProjectParticipants;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectDocumentController extends Controller
{
    use NotifiesProjectParticipants;

    private FileStorageService $storage;

    public function __construct()
    {
        $this->storage = new FileStorageService;
    }

    private function isProjectMember(Request $request, int $projectId): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }
        $roles = $user->getRoleNames();
        if (array_intersect($roles, ['admin', 'pm', 'super_admin'])) {
            return true;
        }

        $staff = StaffProfile::where('email', $user->email)->first();
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

    private function isPmPlus(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        return (bool) array_intersect($user->getRoleNames(), ['admin', 'pm', 'super_admin']);
    }

    public function index(Request $request, int $id): JsonResponse
    {
        if (! $this->isProjectMember($request, $id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $project = Project::findOrFail($id);
        $items = $project->documents()->with('uploader', 'phase', 'task')->orderBy('created_at', 'desc')->get();

        $mapped = $items->map(function ($doc) {
            return [
                'id' => $doc->id,
                'original_name' => $doc->original_filename,
                'mime_type' => $doc->mime_type,
                'size' => $doc->file_size,
                'category' => $doc->category,
                'notes' => $doc->notes,
                'phase_id' => $doc->phase_id,
                'phase_name' => $doc->phase ? $doc->phase->name : null,
                'task_id' => $doc->task_id,
                'task_name' => $doc->task ? $doc->task->title : null,
                'uploaded_by' => $doc->uploader ? ['id' => $doc->uploader->id, 'name' => $doc->uploader->name] : null,
                'uploaded_at' => $doc->created_at,
            ];
        });

        return response()->json(['data' => $mapped]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        if (! $this->isProjectMember($request, $id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $project = Project::findOrFail($id);

        $uploadedFile = $request->file('file');
        if (! $uploadedFile) {
            return response()->json(['error' => 'file is required'], 422);
        }

        $error = FileStorageService::validateUpload($uploadedFile);
        if ($error) {
            return response()->json(['error' => $error], 422);
        }

        $originalName = $uploadedFile->getClientOriginalName();
        $mimeType = $uploadedFile->getClientMimeType();
        $fileSize = $uploadedFile->getSize();
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $storedName = uniqid('doc_', true).'.'.$ext;

        $relativePath = 'uploads/projects/'.$project->id.'/'.$storedName;
        $this->storage->put($relativePath, file_get_contents($uploadedFile->getPathname()), $mimeType);

        $body = $request->all();
        $category = $body['category'] ?? null;
        $notes = $body['notes'] ?? null;
        $phaseId = $body['phase_id'] ?? null;
        $taskId = $body['task_id'] ?? null;

        if ($phaseId) {
            $phase = Phase::find($phaseId);
            if (! $phase || $phase->project_id !== $project->id) {
                return response()->json(['error' => 'Invalid phase_id'], 422);
            }
        }

        if ($taskId) {
            $task = Task::find($taskId);
            if (! $task || ! $task->phase || $task->phase->project_id !== $project->id) {
                return response()->json(['error' => 'Invalid task_id'], 422);
            }
        }

        $user = $request->user();
        $staff = $user->email ? StaffProfile::where('email', $user->email)->first() : null;
        $uploadedBy = $staff ? $staff->id : null;

        $doc = new ProjectDocument;
        $doc->fill([
            'project_id' => $project->id,
            'phase_id' => $phaseId ? (int) $phaseId : null,
            'task_id' => $taskId ? (int) $taskId : null,
            'uploaded_by' => $uploadedBy,
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'category' => $category,
            'notes' => $notes,
        ]);
        $doc->file_path = $relativePath;
        $doc->save();

        $this->notifyProject(
            'document.uploaded',
            ['document_name' => $originalName, 'category' => $category ?? ''],
            $project->id, 'App\\Models\\ProjectDocument', $doc->id,
            'Document Uploaded: '.$originalName,
            'Document '.$originalName.' has been uploaded.',
            '/projects/'.$project->id
        );

        $doc->load('uploader', 'phase', 'task');

        return response()->json($doc, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $doc = ProjectDocument::with('uploader', 'project')->findOrFail($id);
        $project = $doc->project;
        if (! $project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        if (! $this->isProjectMember($request, $project->id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if (! $this->storage->exists($doc->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $params = $request->query();
        if (isset($params['presign']) && $params['presign'] === '1') {
            $url = $this->storage->getPresignedUrl($doc->file_path);
            if ($url) {
                return response()->json(['url' => $url, 'filename' => $doc->original_filename]);
            }
        }

        $contents = $this->storage->get($doc->file_path);
        $disposition = str_starts_with($doc->mime_type, 'image/') ? 'inline' : 'attachment';

        return response($contents, 200, [
            'Content-Type' => $doc->mime_type,
            'Content-Disposition' => $disposition.'; filename="'.$doc->original_filename.'"',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $doc = ProjectDocument::findOrFail($id);
        if (! $this->isPmPlus($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $this->storage->delete($doc->file_path);
        $doc->delete();

        return response()->json(null, 204);
    }
}
