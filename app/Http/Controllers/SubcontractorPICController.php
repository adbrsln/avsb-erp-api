<?php

namespace App\Http\Controllers;

use App\Models\Subcontractor;
use App\Models\SubcontractorPIC;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubcontractorPICController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = SubcontractorPIC::with('subcontractor');

        if (! empty($params['subcontractor_id'])) {
            $query->where('subcontractor_id', $params['subcontractor_id']);
        }

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['subcontractor_id']) || empty($data['name'])) {
            return response()->json(['error' => 'subcontractor_id and name are required'], 422);
        }

        $sub = Subcontractor::find($data['subcontractor_id']);
        if (! $sub) {
            return response()->json(['error' => 'Subcontractor not found'], 404);
        }

        if (! empty($data['is_primary'])) {
            SubcontractorPIC::where('subcontractor_id', $data['subcontractor_id'])
                ->where('is_primary', true)->update(['is_primary' => false]);
        }

        $pic = SubcontractorPIC::create(fillableData(new SubcontractorPIC, $data));
        $pic->load('subcontractor');

        return response()->json($pic, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $pic = SubcontractorPIC::with('subcontractor')->findOrFail($id);

        return response()->json($pic);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $pic = SubcontractorPIC::findOrFail($id);
        $data = $request->all();

        if (! empty($data['is_primary'])) {
            SubcontractorPIC::where('subcontractor_id', $pic->subcontractor_id)
                ->where('is_primary', true)->where('id', '!=', $pic->id)
                ->update(['is_primary' => false]);
        }

        $pic->update(fillableData($pic, $data));
        $pic->load('subcontractor');

        return response()->json($pic);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        SubcontractorPIC::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
