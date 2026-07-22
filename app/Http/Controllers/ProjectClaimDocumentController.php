<?php

namespace App\Http\Controllers;

use App\Models\ProjectClaim;
use App\Models\ProjectClaimDocument;
use App\Models\StaffProfile;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectClaimDocumentController extends Controller
{
    private FileStorageService $storage;

    public function __construct()
    {
        $this->storage = new FileStorageService;
    }

    public function store(Request $request, int $claimId): JsonResponse
    {
        $claim = ProjectClaim::findOrFail($claimId);

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

        $user = $request->user();
        $staff = $user && $user->email ? StaffProfile::where('email', $user->email)->first() : null;

        $doc = ProjectClaimDocument::create([
            'project_claim_id' => $claim->id,
            'uploaded_by' => $staff ? (int) $staff->id : null,
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $relativePath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'notes' => $body['notes'] ?? null,
        ]);

        return response()->json($doc, 201);
    }

    public function download(Request $request, int $docId): JsonResponse
    {
        $doc = ProjectClaimDocument::with('claim.project')->findOrFail($docId);

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

    public function destroy(Request $request, int $docId): JsonResponse
    {
        $doc = ProjectClaimDocument::findOrFail($docId);
        $this->storage->delete($doc->file_path);
        $doc->forceDelete();

        return response()->json(null, 204);
    }
}
