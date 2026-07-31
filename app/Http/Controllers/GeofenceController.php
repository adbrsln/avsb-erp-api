<?php

namespace App\Http\Controllers;

use App\Models\Geofence;
use App\Models\StaffProfile;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GeofenceController extends Controller
{
    use PaginatedResponse;

    private function isAdminPlus(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        return (bool) array_intersect($user->getRoleNames(), ['admin', 'super_admin']);
    }

    private function staffId(Request $request): ?int
    {
        $user = $request->user();
        if (! $user || ! $user->email) {
            return null;
        }

        $staff = StaffProfile::where('email', $user->email)->first();

        return $staff ? (int) $staff->id : null;
    }

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = Geofence::with('project:id,name,project_code')->orderBy('name');

        if (! empty($params['active']) && $params['active'] === '1') {
            $query->where('is_active', true);
        }

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
            'sortable' => ['name', 'radius_meters', 'is_active', 'created_at'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->isAdminPlus($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->all();

        if (empty($data['name'])) {
            return response()->json(['error' => 'Name is required'], 422);
        }
        if (! is_numeric($data['latitude'] ?? null) || ! is_numeric($data['longitude'] ?? null)) {
            return response()->json(['error' => 'Latitude and longitude are required'], 422);
        }
        if (! is_numeric($data['radius_meters'] ?? null) || (int) $data['radius_meters'] <= 0) {
            return response()->json(['error' => 'Radius must be a positive number of meters'], 422);
        }

        $item = Geofence::create(fillableData(new Geofence, $data) + [
            'created_by' => $this->staffId($request),
        ]);

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = Geofence::findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (! $this->isAdminPlus($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $item = Geofence::findOrFail($id);
        $data = $request->all();

        if (isset($data['latitude']) && ! is_numeric($data['latitude'])) {
            return response()->json(['error' => 'Latitude must be numeric'], 422);
        }
        if (isset($data['longitude']) && ! is_numeric($data['longitude'])) {
            return response()->json(['error' => 'Longitude must be numeric'], 422);
        }
        if (isset($data['radius_meters']) && (! is_numeric($data['radius_meters']) || (int) $data['radius_meters'] <= 0)) {
            return response()->json(['error' => 'Radius must be a positive number of meters'], 422);
        }

        $item->update(fillableData($item, $data));

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): Response|JsonResponse
    {
        if (! $this->isAdminPlus($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        Geofence::findOrFail($id)->delete();

        return response()->noContent();
    }
}
