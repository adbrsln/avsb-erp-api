<?php

namespace App\Http\Controllers;

use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectClaim;
use App\Models\ProjectClaimDocument;
use App\Models\StaffProfile;
use App\Services\FileStorageService;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectClaimController extends Controller
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
        $items = $project->claims()->with('submittedBy', 'approvedBy')->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        if (! $this->isProjectMember($request, $id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $project = Project::findOrFail($id);
        $data = $request->all();

        if (empty($data['title'])) {
            return response()->json(['error' => 'title is required'], 422);
        }

        $items = $data['items'] ?? [];
        $amount = 0;
        foreach ($items as $item) {
            $amount += (float) ($item['amount'] ?? 0);
        }

        $staffId = $this->getStaffId($request);

        $claim = ProjectClaim::create([
            'claim_number' => (new NumberingService)->generate('project_claim'),
            'project_id' => $project->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'amount' => $amount,
            'status' => 'draft',
            'submitted_by' => $staffId,
            'items' => $items,
            'notes' => $data['notes'] ?? null,
        ]);

        $claim->load('submittedBy');

        return response()->json($claim, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = ProjectClaim::with('submittedBy', 'approvedBy', 'documents')->findOrFail($id);
        if (! $this->isProjectMember($request, $item->project_id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = ProjectClaim::findOrFail($id);
        if (! $this->isProjectMember($request, $item->project_id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        if ($item->status !== 'draft') {
            return response()->json(['error' => 'Only draft claims can be edited'], 422);
        }
        $data = $request->all();
        if (isset($data['items'])) {
            $amount = 0;
            foreach ($data['items'] as $i) {
                $amount += (float) ($i['amount'] ?? 0);
            }
            $data['amount'] = $amount;
        }
        $item->update(fillableData($item, $data));

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = ProjectClaim::findOrFail($id);
        if (! $this->isPmPlus($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if (in_array($item->status, ['approved', 'paid'])) {
            $user = $request->user();
            $userRoles = $user ? $user->getRoleNames() : [];
            if (empty(array_intersect($userRoles, ['admin', 'finance', 'super_admin']))) {
                return response()->json(['error' => 'Only admin, finance, or super admin can delete approved/paid claims'], 403);
            }
        }

        $item->delete();

        return response()->json(null, 204);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $item = ProjectClaim::findOrFail($id);
        if (! $this->isProjectMember($request, $item->project_id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $staffId = $this->getStaffId($request);
        $item->update(['status' => 'submitted', 'submitted_by' => $staffId, 'submitted_at' => date('Y-m-d H:i:s')]);

        try {
            $recipients = NotificationRecipientResolver::getApprovers('project-claim');
            $project = Project::find($item->project_id);
            NotificationService::queueToMany(
                NotificationEvent::PROJECT_CLAIM_SUBMITTED,
                $recipients,
                [
                    'title' => $item->title,
                    'project_name' => $project?->name ?? 'Unknown',
                    'amount' => number_format($item->amount, 2),
                    'url' => '/approvals?type=project-claim',
                ],
                'App\\Models\\ProjectClaim',
                $item->id
            );
        } catch (\Throwable $e) {
            logger()->error('Notification failed: project-claim.submitted', ['claim_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $item = ProjectClaim::findOrFail($id);
        if ($item->status !== 'submitted') {
            return response()->json(['error' => 'Only submitted claims can be approved'], 422);
        }
        $staffId = $this->getStaffId($request);
        $item->update(['status' => 'approved', 'approved_by' => $staffId, 'approved_at' => date('Y-m-d H:i:s')]);

        try {
            $submitter = StaffProfile::find($item->submitted_by);
            if ($submitter) {
                NotificationService::queue(
                    NotificationEvent::PROJECT_CLAIM_APPROVED,
                    $submitter->email,
                    $submitter->name,
                    [
                        'title' => $item->title,
                        'amount' => number_format($item->amount, 2),
                        'url' => '/projects/'.$item->project_id,
                    ],
                    'App\\Models\\ProjectClaim',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: project-claim.approved', ['claim_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $item = ProjectClaim::findOrFail($id);
        if ($item->status !== 'submitted') {
            return response()->json(['error' => 'Only submitted claims can be rejected'], 422);
        }
        $item->update(['status' => 'rejected']);

        try {
            $submitter = StaffProfile::find($item->submitted_by);
            if ($submitter) {
                NotificationService::queue(
                    NotificationEvent::PROJECT_CLAIM_REJECTED,
                    $submitter->email,
                    $submitter->name,
                    [
                        'title' => $item->title,
                        'amount' => number_format($item->amount, 2),
                        'url' => '/projects/'.$item->project_id,
                    ],
                    'App\\Models\\ProjectClaim',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: project-claim.rejected', ['claim_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function markPaid(Request $request, int $id): JsonResponse
    {
        $item = ProjectClaim::findOrFail($id);
        if ($item->status !== 'approved') {
            return response()->json(['error' => 'Only approved claims can be marked paid'], 422);
        }
        $item->update(['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')]);

        try {
            $submitter = StaffProfile::find($item->submitted_by);
            if ($submitter) {
                NotificationService::queue(
                    NotificationEvent::PROJECT_CLAIM_PAID,
                    $submitter->email,
                    $submitter->name,
                    [
                        'title' => $item->title,
                        'amount' => number_format($item->amount, 2),
                        'url' => '/projects/'.$item->project_id,
                    ],
                    'App\\Models\\ProjectClaim',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: project-claim.paid', ['claim_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function listDocuments(Request $request, int $id): JsonResponse
    {
        $claim = ProjectClaim::findOrFail($id);
        if (! $this->isProjectMember($request, $claim->project_id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $docs = ProjectClaimDocument::with('uploader:id,name')
            ->where('project_claim_id', $claim->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $docs]);
    }

    public function uploadDocument(Request $request, int $id): JsonResponse
    {
        $claim = ProjectClaim::findOrFail($id);
        if (! $this->isProjectMember($request, $claim->project_id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $file = $request->file('file');
        if (! $file) {
            return response()->json(['error' => 'No file uploaded'], 422);
        }

        $body = $request->all();
        $originalName = $file->getClientOriginalName();

        $docError = FileStorageService::validateUpload($file);
        if ($docError) {
            return response()->json(['error' => $docError], 422);
        }

        $mimeType = $file->getClientMimeType() ?: 'application/octet-stream';
        $fileSize = $file->getSize();
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $storedName = uniqid('pcl_', true).'.'.$ext;

        $relativePath = 'uploads/project-claims/'.$claim->id.'/'.$storedName;
        $this->storage->put($relativePath, file_get_contents($file->getPathname()), $mimeType);

        $doc = new ProjectClaimDocument;
        $doc->fill([
            'project_claim_id' => $claim->id,
            'uploaded_by' => $this->getStaffId($request),
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'notes' => $body['notes'] ?? null,
        ]);
        $doc->file_path = $relativePath;
        $doc->save();

        return response()->json($doc, 201);
    }

    public function serveDocument(Request $request, int $docId): JsonResponse
    {
        $doc = ProjectClaimDocument::with('claim.project')->findOrFail($docId);
        if (! $this->isProjectMember($request, $doc->claim?->project_id ?? 0)) {
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

    public function deleteDocument(Request $request, int $docId): JsonResponse
    {
        $doc = ProjectClaimDocument::with('claim')->findOrFail($docId);
        if (! $this->isPmPlus($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $staffId = $this->getStaffId($request);
        if (! $staffId || ($doc->uploaded_by !== $staffId && ! $this->isPmPlus($request))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $this->storage->delete($doc->file_path);
        $doc->forceDelete();

        return response()->json(null, 204);
    }
}
