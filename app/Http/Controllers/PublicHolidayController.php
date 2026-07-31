<?php

namespace App\Http\Controllers;

use App\Models\PublicHoliday;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicHolidayController extends Controller
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

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = PublicHoliday::orderBy('date');

        if (! empty($params['year'])) {
            $year = (int) $params['year'];
            $query->where(function ($q) use ($year) {
                $q->where('year', $year)
                    ->orWhere(function ($q2) use ($year) {
                        $q2->where('is_recurring', true)
                            ->where(fn ($q3) => $q3->whereNull('year')->orWhere('year', '<=', $year));
                    });
            });
        }

        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where('name', 'like', "%{$s}%");
        }

        if (! empty($params['all']) && $params['all'] === 'true') {
            return response()->json(['data' => $query->get()]);
        }

        return $this->paginate($query, $params, [
            'sortable' => ['name', 'date', 'is_recurring', 'created_at'],
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
        if (empty($data['date'])) {
            return response()->json(['error' => 'Date is required'], 422);
        }

        $isRecurring = ! empty($data['is_recurring']);
        $year = ! empty($data['year']) ? (int) $data['year'] : (int) date('Y', strtotime($data['date']));

        $exists = PublicHoliday::whereDate('date', $data['date'])
            ->where('is_recurring', $isRecurring)
            ->exists();
        if ($exists) {
            return response()->json(['error' => 'A holiday already exists for this date'], 422);
        }

        $item = PublicHoliday::create([
            'name' => $data['name'],
            'date' => $data['date'],
            'year' => $isRecurring ? null : $year,
            'is_recurring' => $isRecurring,
        ]);

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = PublicHoliday::findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (! $this->isAdminPlus($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $item = PublicHoliday::findOrFail($id);
        $data = $request->all();

        if (isset($data['name']) && empty($data['name'])) {
            return response()->json(['error' => 'Name is required'], 422);
        }
        if (isset($data['date']) && empty($data['date'])) {
            return response()->json(['error' => 'Date is required'], 422);
        }

        $item->update([
            'name' => $data['name'] ?? $item->name,
            'date' => $data['date'] ?? $item->date,
            'is_recurring' => array_key_exists('is_recurring', $data) ? (bool) $data['is_recurring'] : $item->is_recurring,
            'year' => array_key_exists('is_recurring', $data)
                ? (($data['is_recurring'] ?? false) ? null : ((int) $data['year'] ?? date('Y', strtotime($data['date'] ?? $item->date))))
                : $item->year,
        ]);

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): Response|JsonResponse
    {
        if (! $this->isAdminPlus($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        PublicHoliday::findOrFail($id)->delete();

        return response()->noContent();
    }
}
