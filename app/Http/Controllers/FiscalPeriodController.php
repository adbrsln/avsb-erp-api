<?php

namespace App\Http\Controllers;

use App\Models\FiscalPeriod;
use App\Services\PeriodClosingService;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FiscalPeriodController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = FiscalPeriod::with('closedBy:id,name')->orderByDesc('start_date');

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['name']) || empty($data['start_date']) || empty($data['end_date'])) {
            return response()->json(['error' => 'name, start_date and end_date are required'], 422);
        }

        $exists = FiscalPeriod::where('start_date', $data['start_date'])
            ->where('end_date', $data['end_date'])
            ->exists();
        if ($exists) {
            return response()->json(['error' => 'A period with these dates already exists'], 422);
        }

        $period = FiscalPeriod::create([
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'type' => $data['type'] ?? 'month',
        ]);

        return response()->json($period, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $period = FiscalPeriod::with('closedBy:id,name', 'openingBalanceEntry.lines.account')->findOrFail($id);

        return response()->json($period);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $period = FiscalPeriod::findOrFail($id);

        if ($period->status !== 'open') {
            return response()->json(['error' => 'Only open periods can be edited'], 422);
        }

        $data = $request->all();
        $period->update(fillableData($period, $data));

        return response()->json($period);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $period = FiscalPeriod::findOrFail($id);

        if ($period->status !== 'open') {
            return response()->json(['error' => 'Only open periods can be deleted'], 422);
        }

        if ($period->opening_balance_entry_id) {
            $period->update(['opening_balance_entry_id' => null]);
        }

        $period->delete();

        return response()->json(null, 204);
    }

    public function close(Request $request, int $id): JsonResponse
    {
        $period = FiscalPeriod::findOrFail($id);

        if ($period->status !== 'open') {
            return response()->json(['error' => 'Only open periods can be closed'], 422);
        }

        $userId = $request->user()?->id;
        $result = PeriodClosingService::close($period, $userId);

        $period->load('closedBy:id,name');

        return response()->json([
            'period' => $period->fresh(),
            'closing_entry_id' => $result['entry_id'],
            'net_profit' => $result['net_profit'],
        ]);
    }

    public function reopen(Request $request, int $id): JsonResponse
    {
        $period = FiscalPeriod::findOrFail($id);

        if (! in_array($period->status, ['closed', 'locked'])) {
            return response()->json(['error' => 'Only closed or locked periods can be reopened'], 422);
        }

        $period->update([
            'status' => 'open',
            'closed_at' => null,
            'closed_by' => null,
        ]);

        return response()->json($period);
    }
}
