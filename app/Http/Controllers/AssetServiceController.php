<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetService;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetServiceController extends Controller
{
    private FileStorageService $storage;

    public function __construct()
    {
        $this->storage = new FileStorageService;
    }

    public function index(Request $request, int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $services = $asset->services()->orderBy('service_date', 'desc')->get();

        return response()->json(['data' => $services]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $data = $request->all();

        if (empty($data['service_type']) || empty($data['service_date'])) {
            return response()->json(['error' => 'service_type and service_date are required'], 422);
        }

        $service = $asset->services()->create(fillableData(new AssetService, $data));

        $file = $request->file('document');
        if ($file && $file->isValid()) {
            $docError = FileStorageService::validateUpload($file);
            if ($docError) {
                return response()->json(['error' => $docError], 422);
            }
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $storedName = 'service_'.uniqid('', true).'.'.$ext;
            $path = 'uploads/services/'.$asset->id.'/'.$storedName;
            $this->storage->put($path, file_get_contents($file->getPathname()), $file->getClientMimeType());
            $service->document_path = $path;
            $service->save();
        }

        if (! empty($data['next_service_date'])) {
            $asset->update(['next_service_date' => $data['next_service_date']]);
        }

        return response()->json($service, 201);
    }

    public function update(Request $request, int $id, int $serviceId): JsonResponse
    {
        $service = AssetService::where('asset_id', $id)->findOrFail($serviceId);
        $data = $request->all();

        $file = $request->file('document');
        if ($file && $file->isValid()) {
            $docError = FileStorageService::validateUpload($file);
            if ($docError) {
                return response()->json(['error' => $docError], 422);
            }
            if ($service->document_path) {
                $this->storage->delete($service->document_path);
            }
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $storedName = 'service_'.uniqid('', true).'.'.$ext;
            $path = 'uploads/services/'.$id.'/'.$storedName;
            $this->storage->put($path, file_get_contents($file->getPathname()), $file->getClientMimeType());
            $data['document_path'] = $path;
        }

        $service->update(fillableData($service, $data));

        if (! empty($data['next_service_date'])) {
            $service->asset->update(['next_service_date' => $data['next_service_date']]);
        }

        return response()->json($service);
    }

    public function destroy(Request $request, int $id, int $serviceId): JsonResponse
    {
        $service = AssetService::where('asset_id', $id)->findOrFail($serviceId);
        if ($service->document_path) {
            $this->storage->delete($service->document_path);
        }
        $service->delete();

        return response()->json(null, 204);
    }

    public function download(Request $request, int $id, int $serviceId): JsonResponse
    {
        $service = AssetService::where('asset_id', $id)->findOrFail($serviceId);
        if (! $service->document_path) {
            return response()->json(['error' => 'No document attached'], 404);
        }

        if (! $this->storage->exists($service->document_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $params = $request->query();
        if (isset($params['presign']) && $params['presign'] === '1') {
            $url = $this->storage->getPresignedUrl($service->document_path);
            if ($url) {
                return response()->json(['url' => $url, 'filename' => 'service.'.pathinfo($service->document_path, PATHINFO_EXTENSION)]);
            }
        }

        $contents = $this->storage->get($service->document_path);

        $ext = pathinfo($service->document_path, PATHINFO_EXTENSION);
        $mime = $ext === 'pdf' ? 'application/pdf' : ($ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'application/octet-stream');

        return response($contents, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="service.'.$ext.'"',
        ]);
    }
}
