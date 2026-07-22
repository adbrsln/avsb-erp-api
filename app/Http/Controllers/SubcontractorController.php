<?php

namespace App\Http\Controllers;

use App\Models\ProjectSubcontractor;
use App\Models\Subcontractor;
use App\Models\SubcontractorClaim;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubcontractorController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = Subcontractor::withCount('pics');

        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('company_name', 'like', "%{$s}%")
                    ->orWhere('subcontractor_code', 'like', "%{$s}%");
            });
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['all']) && $params['all'] === 'true') {
            return response()->json(['data' => $query->orderBy('company_name')->get()]);
        }

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['company_name'])) {
            return response()->json(['error' => 'company_name is required'], 422);
        }

        $data['subcontractor_code'] = (new NumberingService)->generate('subcontractor');

        $subcontractor = Subcontractor::create(fillableData(new Subcontractor, $data));

        return response()->json($subcontractor, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $subcontractor = Subcontractor::with('projectAssignments.project', 'projectAssignments.subcontractor', 'pics')
            ->findOrFail($id);

        return response()->json($subcontractor);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $subcontractor = Subcontractor::findOrFail($id);
        unset($data['subcontractor_code']);

        $subcontractor->update(fillableData($subcontractor, $data));

        return response()->json($subcontractor);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $subcontractor = Subcontractor::findOrFail($id);

        $activeAssignments = ProjectSubcontractor::where('subcontractor_id', $subcontractor->id)
            ->where('status', 'active')
            ->exists();

        if ($activeAssignments) {
            return response()->json([
                'error' => 'Cannot delete subcontractor with active project assignments. Please remove or complete all active assignments first.',
            ], 422);
        }

        $subcontractor->delete();

        return response()->json(null, 204);
    }

    public function projects(Request $request, int $id): JsonResponse
    {
        $rows = ProjectSubcontractor::with('project:id,name')
            ->where('subcontractor_id', $id)
            ->get();

        return response()->json($rows);
    }

    public function claims(Request $request, int $id): JsonResponse
    {
        $rows = SubcontractorClaim::with('projectSubcontractor.project:id,name')
            ->whereHas('projectSubcontractor', fn ($q) => $q->where('subcontractor_id', $id))
            ->orderByDesc('claim_date')
            ->get();

        return response()->json($rows);
    }
}
