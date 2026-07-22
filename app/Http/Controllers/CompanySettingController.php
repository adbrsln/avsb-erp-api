<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanySettingController extends Controller
{
    private FileStorageService $storage;

    public function __construct()
    {
        $this->storage = new FileStorageService;
    }

    public function show(Request $request): JsonResponse
    {
        $settings = CompanySetting::first();
        if (! $settings) {
            $settings = CompanySetting::create(fillableData(new CompanySetting, ['company_name' => '']));
        }

        return response()->json($settings->toArray());
    }

    public function update(Request $request): JsonResponse
    {
        $body = $request->all();
        $settings = CompanySetting::first();

        if (! $settings) {
            $settings = CompanySetting::create(fillableData(new CompanySetting, [
                'company_name' => $body['company_name'] ?? '',
                'address' => $body['address'] ?? null,
                'reg_no' => $body['reg_no'] ?? null,
                'epf_no' => $body['epf_no'] ?? null,
                'socso_no' => $body['socso_no'] ?? null,
                'eis_no' => $body['eis_no'] ?? null,
            ]));
        } else {
            $settings->update(fillableData($settings, [
                'company_name' => $body['company_name'] ?? $settings->company_name,
                'address' => $body['address'] ?? $settings->address,
                'reg_no' => $body['reg_no'] ?? $settings->reg_no,
                'epf_no' => $body['epf_no'] ?? $settings->epf_no,
                'socso_no' => $body['socso_no'] ?? $settings->socso_no,
                'eis_no' => $body['eis_no'] ?? $settings->eis_no,
                'socso_24h_phase' => $body['socso_24h_phase'] ?? $settings->socso_24h_phase ?? 1,
            ]));
        }

        return response()->json($settings->toArray());
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $file = $request->file('logo');
        if (! $file || ! $file->isValid()) {
            return response()->json(['error' => 'No valid logo file uploaded'], 422);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
            return response()->json(['error' => 'Logo must be PNG, JPG, GIF, or WebP'], 422);
        }

        $path = 'company/logo.'.$ext;
        $contents = file_get_contents($file->getRealPath());
        $this->storage->put($path, $contents, $file->getMimeType());

        $settings = CompanySetting::first();
        if (! $settings) {
            $settings = CompanySetting::create(['company_name' => '', 'logo_path' => $path]);
        } else {
            if ($settings->logo_path && $settings->logo_path !== $path) {
                try {
                    $this->storage->delete($settings->logo_path);
                } catch (\Throwable $e) {
                    //
                }
            }
            $settings->update(['logo_path' => $path]);
        }

        return response()->json(['logo_path' => $path]);
    }

    public function deleteLogo(Request $request): JsonResponse
    {
        $settings = CompanySetting::first();
        if ($settings && $settings->logo_path) {
            try {
                $this->storage->delete($settings->logo_path);
            } catch (\Throwable $e) {
                //
            }
            $settings->update(['logo_path' => null]);
        }

        return response()->json(['message' => 'Logo deleted']);
    }

    public function serveLogo(Request $request): JsonResponse
    {
        $settings = CompanySetting::first();
        if (! $settings || ! $settings->logo_path || ! $this->storage->exists($settings->logo_path)) {
            return response()->json(['error' => 'No logo uploaded'], 404);
        }

        $url = $this->storage->getPresignedUrl($settings->logo_path);
        if ($url) {
            return response()->json(['url' => $url]);
        }

        $contents = $this->storage->get($settings->logo_path);
        $ext = pathinfo($settings->logo_path, PATHINFO_EXTENSION);
        $mime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp'][$ext] ?? 'application/octet-stream';

        return response()->json(['data' => base64_encode($contents), 'mime' => $mime]);
    }
}
