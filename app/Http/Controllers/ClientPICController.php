<?php

namespace App\Http\Controllers;

use App\Models\ClientPIC;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientPICController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $params = $request->all();
        $query = ClientPIC::with('client');

        if (! empty($params['client_id'])) {
            $query->where('client_id', $params['client_id']);
        }

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['client_id'])) {
            return response()->json(['error' => 'client_id is required'], 422);
        }
        if (empty($data['name'])) {
            return response()->json(['error' => 'name is required'], 422);
        }

        $item = ClientPIC::create(fillableData(new ClientPIC, $data));
        $item->load('client');

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = ClientPIC::with('client')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = ClientPIC::findOrFail($id);
        $item->update(fillableData($item, $request->all()));
        $item->load('client');

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        ClientPIC::findOrFail($id)->delete();

        return response()->noContent();
    }
}
