<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Vendor::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('vendor_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($request->input('all') === 'true') {
            return response()->json(['data' => $query->orderBy('company_name')->get()]);
        }

        return $this->paginate($query, $request->all());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['company_name'])) {
            return response()->json(['error' => 'company_name is required'], 422);
        }

        $data['vendor_code'] = (new NumberingService)->generate('vendor');
        $vendor = Vendor::create(fillableData(new Vendor, $data));

        return response()->json($vendor, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);

        return response()->json($vendor);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $vendor = Vendor::findOrFail($id);
        unset($data['vendor_code']);
        $vendor->update(fillableData($vendor, $data));

        return response()->json($vendor);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        Vendor::findOrFail($id)->delete();

        return response()->noContent();
    }
}
