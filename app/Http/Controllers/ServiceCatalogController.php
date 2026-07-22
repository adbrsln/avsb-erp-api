<?php

namespace App\Http\Controllers;

use App\Models\ServiceCatalogItem;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceCatalogController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $params = $request->all();
        $query = ServiceCatalogItem::query();

        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhere('category', 'like', "%{$s}%");
            });
        }

        if (! empty($params['category'])) {
            $query->where('category', $params['category']);
        }

        if (isset($params['is_active'])) {
            $query->where('is_active', $params['is_active'] === 'true' || $params['is_active'] === '1');
        }

        if (! empty($params['all']) && $params['all'] === 'true') {
            $query->orderBy('name');

            return response()->json(['data' => $query->get()]);
        }

        return $this->paginate($query, $params, [
            'sortable' => ['name', 'category', 'unit_rate', 'unit', 'is_active', 'created_at'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['name'])) {
            return response()->json(['error' => 'name is required'], 422);
        }
        if (empty($data['unit'])) {
            return response()->json(['error' => 'unit is required'], 422);
        }

        $item = ServiceCatalogItem::create(fillableData(new ServiceCatalogItem, $data));

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = ServiceCatalogItem::findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = ServiceCatalogItem::findOrFail($id);
        $item->update(fillableData($item, $request->all()));

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        ServiceCatalogItem::findOrFail($id)->delete();

        return response()->noContent();
    }
}
