<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\StaffProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetMovementController extends Controller
{
    public function index(Request $request, int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $movements = $asset->movements()
            ->with('fromStaff', 'toStaff', 'creator')
            ->orderBy('movement_date', 'desc')
            ->get();

        return response()->json(['data' => $movements]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $data = $request->all();

        if (empty($data['movement_type']) || empty($data['movement_date'])) {
            return response()->json(['error' => 'movement_type and movement_date are required'], 422);
        }

        $user = $request->user();
        $staff = $user->email ? StaffProfile::where('email', $user->email)->first() : null;
        $data['created_by'] = $staff?->id;
        $data['asset_id'] = $asset->id;

        $movement = AssetMovement::create(fillableData(new AssetMovement, $data));

        $update = [];
        if (! empty($data['to_location'])) {
            $update['location'] = $data['to_location'];
        }
        if (! empty($data['to_staff_id'])) {
            $update['assigned_to'] = $data['to_staff_id'];
        }
        if (! empty($update)) {
            $asset->update($update);
        }

        $movement->load('fromStaff', 'toStaff', 'creator');

        return response()->json($movement, 201);
    }

    public function destroy(Request $request, int $id, int $movementId): JsonResponse
    {
        $movement = AssetMovement::where('asset_id', $id)->findOrFail($movementId);
        $movement->delete();

        return response()->json(null, 204);
    }
}
