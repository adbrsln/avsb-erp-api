<?php

namespace App\Http\Controllers;

use App\Models\Phase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhaseStaffController extends Controller
{
    public function index(Request $request, int $id): JsonResponse
    {
        $phase = Phase::with(['staff' => fn ($q) => $q->select('id', 'name')])->findOrFail($id);

        return response()->json(['data' => $phase->staff]);
    }

    public function sync(Request $request, int $id): JsonResponse
    {
        $phase = Phase::findOrFail($id);
        $data = $request->all();
        $staffIds = $data['staff_ids'] ?? [];
        $phase->staff()->sync($staffIds);
        $phase->load(['staff' => fn ($q) => $q->select('id', 'name')]);

        return response()->json(['data' => $phase->staff]);
    }
}
