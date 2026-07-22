<?php

namespace App\Http\Controllers;

use App\Models\PhaseTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhaseTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = PhaseTemplate::orderBy('order');

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();
        if (empty($data['name']) || empty($data['code'])) {
            return response()->json(['error' => 'name and code are required'], 422);
        }
        $item = PhaseTemplate::create(fillableData(new PhaseTemplate, $data));

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = PhaseTemplate::findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = PhaseTemplate::findOrFail($id);
        $item->update(fillableData($item, $request->all()));

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        PhaseTemplate::findOrFail($id)->delete();

        return response()->json(null, 204);
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
