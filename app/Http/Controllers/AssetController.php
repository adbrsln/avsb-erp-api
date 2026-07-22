<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\StaffProfile;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AssetController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = Asset::with('licenses', 'assignedStaff:id,name');

        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('asset_code', 'like', "%{$s}%")
                    ->orWhere('make', 'like', "%{$s}%")
                    ->orWhere('model', 'like', "%{$s}%")
                    ->orWhere('serial_number', 'like', "%{$s}%")
                    ->orWhere('registration_number', 'like', "%{$s}%");
            });
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['asset_type'])) {
            $query->where('asset_type', $params['asset_type']);
        }

        if (! empty($params['expiring'])) {
            $query->whereHas('licenses', function ($q) {
                $q->where('expiry_date', '>=', now())
                    ->where('expiry_date', '<=', now()->addDays(30));
            });
        }

        return $this->paginate($query->orderBy('name'), $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['name']) || empty($data['asset_type'])) {
            return response()->json(['error' => 'name and asset_type are required'], 422);
        }

        $user = $request->user();
        $staff = $user->email ? StaffProfile::where('email', $user->email)->first() : null;
        $data['created_by'] = $staff?->id;

        $asset = Asset::create(fillableData(new Asset, $data));
        $asset->load(['licenses', 'assignedStaff:id,name']);

        return response()->json($asset, 201);
    }

    public function findByCode(Request $request, string $assetCode): JsonResponse
    {
        $asset = Asset::with(['licenses', 'movements', 'services', 'assignedStaff:id,name', 'creator:id,name'])
            ->where('asset_code', $assetCode)
            ->first();
        if (! $asset) {
            return response()->json(['error' => 'Asset not found'], 404);
        }

        return response()->json($asset);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $asset = Asset::with(['licenses', 'movements', 'services', 'assignedStaff:id,name', 'creator:id,name'])
            ->findOrFail($id);

        return response()->json($asset);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $asset = Asset::findOrFail($id);

        $asset->update(fillableData($asset, $data));
        $asset->load(['licenses', 'assignedStaff:id,name']);

        return response()->json($asset);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        Asset::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    public function types(Request $request): JsonResponse
    {
        $types = Asset::select('asset_type')->distinct()->orderBy('asset_type')->pluck('asset_type');

        return response()->json(['data' => $types]);
    }

    public function publicAsset(Request $request, string $assetCode): Response
    {
        $asset = Asset::with('assignedStaff:id,name')
            ->where('asset_code', $assetCode)
            ->first();

        if (! $asset) {
            return response('<html><body><h1>Asset not found</h1></body></html>', 404, ['Content-Type' => 'text/html']);
        }

        $assigned = $asset->assignedStaff?->name ?? '—';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$asset->asset_code} — AVSB ERP</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #f4f4f5; color: #18181b; font-size: 14px; line-height: 1.6;
    display: flex; justify-content: center; padding: 24px 16px;
  }
  .card {
    max-width: 480px; width: 100%; background: #fff;
    border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
  }
  .header {
    background: #ca2316; color: #fff; padding: 20px 24px;
  }
  .header h1 { font-size: 13px; font-weight: 600; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
  .header .code { font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
  .header .name { font-size: 16px; opacity: 0.85; margin-top: 4px; }
  .body { padding: 20px 24px; }
  .row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #e4e4e7; }
  .row:last-child { border: none; }
  .label { color: #71717a; font-size: 12px; }
  .value { font-weight: 500; font-size: 13px; text-align: right; }
  .footer { text-align: center; padding: 12px 24px; font-size: 11px; color: #a1a1aa; border-top: 1px solid #e4e4e7; }
  .status-badge {
    display: inline-block; padding: 1px 8px; border-radius: 9999px;
    font-size: 11px; font-weight: 600; text-transform: capitalize;
  }
  .status-active { background: #dcfce7; color: #166534; }
  .status-maintenance { background: #fef3c7; color: #92400e; }
  .status-retired { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body>
<div class="card">
  <div class="header">
    <h1>AVSB ERP — Asset</h1>
    <div class="code">{$asset->asset_code}</div>
    <div class="name">{$asset->name}</div>
  </div>
  <div class="body">
    <div class="row">
      <span class="label">Type</span>
      <span class="value">{$asset->asset_type}</span>
    </div>
    <div class="row">
      <span class="label">Make / Model</span>
      <span class="value">{$asset->make} {$asset->model}</span>
    </div>
    <div class="row">
      <span class="label">Serial Number</span>
      <span class="value">{($asset->serial_number ?? '—')}</span>
    </div>
    <div class="row">
      <span class="label">Year</span>
      <span class="value">{($asset->year ?? '—')}</span>
    </div>
    <div class="row">
      <span class="label">Location</span>
      <span class="value">{($asset->location ?? '—')}</span>
    </div>
    <div class="row">
      <span class="label">Status</span>
      <span class="value"><span class="status-badge status-{$asset->status}">{$asset->status}</span></span>
    </div>
    <div class="row">
      <span class="label">Assigned To</span>
      <span class="value">{$assigned}</span>
    </div>
  </div>
  <div class="footer">AVSB ERP — Azam Ventures Sdn Bhd</div>
</div>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html']);
    }
}
