<?php

namespace App\Http\Controllers;

use App\Models\ProjectType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = ProjectType::with('phaseTemplates')->orderBy('sort_order');

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();
        if (empty($data['name']) || empty($data['code'])) {
            return response()->json(['error' => 'name and code are required'], 422);
        }
        $item = ProjectType::create(fillableData(new ProjectType, $data));

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = ProjectType::with('phaseTemplates')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = ProjectType::findOrFail($id);
        $item->update(fillableData($item, $request->all()));

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        ProjectType::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    public function templates(Request $request, int $id): JsonResponse
    {
        $type = ProjectType::findOrFail($id);

        return response()->json(['data' => $type->phaseTemplates]);
    }

    public function syncTemplates(Request $request, int $id): JsonResponse
    {
        $type = ProjectType::findOrFail($id);
        $data = $request->all();
        $templateIds = $data['template_ids'] ?? [];

        $sync = [];
        foreach ($templateIds as $i => $tid) {
            $sync[$tid] = ['sort_order' => $i + 1];
        }
        $type->phaseTemplates()->sync($sync);

        $templates = $type->phaseTemplates()->orderBy('pivot_sort_order')->get();

        return response()->json(['data' => $templates]);
    }

    private function paginate(Builder $query, array $params): JsonResponse
    {
        $page = max(1, intval($params['page'] ?? 1));
        $perPage = min(100, max(1, intval($params['per_page'] ?? 15)));

        $total = $query->count();
        $items = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }
}
