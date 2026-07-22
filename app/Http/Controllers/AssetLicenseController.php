<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetLicense;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetLicenseController extends Controller
{
    private FileStorageService $storage;

    public function __construct()
    {
        $this->storage = new FileStorageService;
    }

    public function index(Request $request, int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $licenses = $asset->licenses()->orderBy('expiry_date')->get();

        return response()->json(['data' => $licenses]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $data = $request->all();

        if (empty($data['license_type']) || empty($data['expiry_date'])) {
            return response()->json(['error' => 'license_type and expiry_date are required'], 422);
        }

        $license = $asset->licenses()->create(fillableData(new AssetLicense, $data));

        $file = $request->file('document');
        if ($file && $file->isValid()) {
            $docError = FileStorageService::validateUpload($file);
            if ($docError) {
                return response()->json(['error' => $docError], 422);
            }
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $storedName = 'license_'.uniqid('', true).'.'.$ext;
            $path = 'uploads/licenses/'.$asset->id.'/'.$storedName;
            $this->storage->put($path, file_get_contents($file->getPathname()), $file->getClientMimeType());
            $license->document_path = $path;
            $license->save();
        }

        $license->load('asset');

        return response()->json($license, 201);
    }

    public function update(Request $request, int $id, int $licenseId): JsonResponse
    {
        $license = AssetLicense::where('asset_id', $id)->findOrFail($licenseId);
        $data = $request->all();

        $file = $request->file('document');
        if ($file && $file->isValid()) {
            $docError = FileStorageService::validateUpload($file);
            if ($docError) {
                return response()->json(['error' => $docError], 422);
            }
            if ($license->document_path) {
                $this->storage->delete($license->document_path);
            }
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $storedName = 'license_'.uniqid('', true).'.'.$ext;
            $path = 'uploads/licenses/'.$id.'/'.$storedName;
            $this->storage->put($path, file_get_contents($file->getPathname()), $file->getClientMimeType());
            $data['document_path'] = $path;
        }

        $license->update(fillableData($license, $data));

        return response()->json($license);
    }

    public function destroy(Request $request, int $id, int $licenseId): JsonResponse
    {
        $license = AssetLicense::where('asset_id', $id)->findOrFail($licenseId);
        if ($license->document_path) {
            $this->storage->delete($license->document_path);
        }
        $license->delete();

        return response()->json(null, 204);
    }

    public function download(Request $request, int $id, int $licenseId): JsonResponse
    {
        $license = AssetLicense::where('asset_id', $id)->findOrFail($licenseId);
        if (! $license->document_path) {
            return response()->json(['error' => 'No document attached'], 404);
        }

        if (! $this->storage->exists($license->document_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $params = $request->query();
        if (isset($params['presign']) && $params['presign'] === '1') {
            $url = $this->storage->getPresignedUrl($license->document_path);
            if ($url) {
                return response()->json(['url' => $url, 'filename' => 'license.'.pathinfo($license->document_path, PATHINFO_EXTENSION)]);
            }
        }

        $contents = $this->storage->get($license->document_path);

        $ext = pathinfo($license->document_path, PATHINFO_EXTENSION);
        $mime = $ext === 'pdf' ? 'application/pdf' : ($ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'application/octet-stream');

        return response($contents, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="license.'.$ext.'"',
        ]);
    }
}
