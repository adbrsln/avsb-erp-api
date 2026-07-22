<?php

namespace App\Http\Controllers;

use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = ServiceType::all();

        return response()->json($items);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = ServiceType::findOrFail($id);

        return response()->json($item);
    }
}
