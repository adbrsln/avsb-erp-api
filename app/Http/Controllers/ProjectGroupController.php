<?php

namespace App\Http\Controllers;

use App\Models\ProjectGroup;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectGroupController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $params = $request->all();
        $query = ProjectGroup::orderBy('sort_order')->orderBy('name');

        if (! empty($params['all']) && $params['all'] === 'true') {
            return response()->json(['data' => $query->get()]);
        }

        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }

        return $this->paginate($query, $params, [
            'sortable' => ['name', 'sort_order', 'created_at'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();
        if (empty($data['name'])) {
            return response()->json(['error' => 'name is required'], 422);
        }

        $item = ProjectGroup::create(fillableData(new ProjectGroup, $data));

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = ProjectGroup::findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = ProjectGroup::findOrFail($id);
        $item->update(fillableData($item, $request->all()));

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        ProjectGroup::findOrFail($id)->delete();

        return response()->noContent();
    }
}
