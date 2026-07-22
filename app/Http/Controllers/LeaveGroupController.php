<?php

namespace App\Http\Controllers;

use App\Models\LeaveGroup;
use App\Models\LeaveGroupEntitlement;
use App\Models\StaffLeaveBalance;
use App\Models\StaffProfile;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveGroupController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = LeaveGroup::with('entitlements')->orderBy('name');

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();
        if (empty($data['name'])) {
            return response()->json(['error' => 'name is required'], 422);
        }
        $item = LeaveGroup::create(fillableData(new LeaveGroup, $data));
        if (! empty($data['entitlements'])) {
            foreach ($data['entitlements'] as $e) {
                $item->entitlements()->create($e);
            }
            $item->load('entitlements');
        }

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = LeaveGroup::with('entitlements')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = LeaveGroup::findOrFail($id);
        $data = $request->all();
        $item->update(fillableData($item, $data));

        if (isset($data['entitlements'])) {
            $item->entitlements()->delete();
            foreach ($data['entitlements'] as $e) {
                $item->entitlements()->create($e);
            }
        }

        $item->load('entitlements');

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        LeaveGroup::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    public function entitlements(Request $request, int $id): JsonResponse
    {
        $staffId = $id;
        $year = $request->query('year', date('Y'));
        $balances = StaffLeaveBalance::where('staff_id', $staffId)
            ->where('year', $year)
            ->get();

        return response()->json(['data' => $balances]);
    }

    public function deleteEntitlement(Request $request, int $id): JsonResponse
    {
        $item = LeaveGroupEntitlement::findOrFail($id);
        $item->delete();

        return response()->json(null, 204);
    }

    public function adjustBalance(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $balance = StaffLeaveBalance::findOrFail($id);
        $balance->update([
            'adjusted' => $data['adjusted'] ?? $balance->adjusted,
            'balance' => $balance->entitled - $balance->used + ($data['adjusted'] ?? $balance->adjusted),
        ]);
        $balance->refresh();

        return response()->json($balance);
    }

    public function seedBalance(Request $request, int $id): JsonResponse
    {
        $staffId = $id;
        $staff = StaffProfile::with('leaveGroup.entitlements')->findOrFail($staffId);
        if (! $staff->leaveGroup) {
            return response()->json(['error' => 'Staff has no leave group assigned'], 422);
        }
        $year = date('Y');
        $created = [];
        foreach ($staff->leaveGroup->entitlements as $e) {
            $balance = StaffLeaveBalance::firstOrCreate(
                ['staff_id' => $staffId, 'type' => $e->type, 'year' => $year],
                [
                    'entitled' => $e->days_entitled,
                    'used' => 0,
                    'adjusted' => 0,
                    'balance' => $e->days_entitled,
                ]
            );
            $created[] = $balance;
        }

        return response()->json(['data' => $created]);
    }

    public function staffBalance(Request $request, int $id): JsonResponse
    {
        $year = $request->input('year', date('Y'));
        $balances = StaffLeaveBalance::where('staff_id', $id)
            ->where('year', $year)
            ->get();

        return response()->json(['data' => $balances]);
    }

    public function carryForward(Request $request, int $id): JsonResponse
    {
        $staffId = $id;
        $data = $request->all();
        $fromYear = $data['from_year'] ?? (date('Y') - 1);
        $toYear = $data['to_year'] ?? date('Y');
        $items = $data['items'] ?? [];

        if (empty($items)) {
            return response()->json(['error' => 'items is required'], 422);
        }

        $staff = StaffProfile::with('leaveGroup.entitlements')->findOrFail($staffId);

        $results = [];
        foreach ($items as $item) {
            $type = $item['type'] ?? '';
            $days = (float) ($item['days'] ?? 0);
            if ($days <= 0) {
                continue;
            }

            $fromBalance = StaffLeaveBalance::where('staff_id', $staffId)
                ->where('type', $type)->where('year', $fromYear)->first();

            if (! $fromBalance || $fromBalance->balance < $days) {
                continue;
            }

            $fromBalance->balance -= $days;
            $fromBalance->save();

            $toBalance = StaffLeaveBalance::firstOrCreate(
                ['staff_id' => $staffId, 'type' => $type, 'year' => $toYear],
                [
                    'entitled' => 0,
                    'used' => 0,
                    'adjusted' => 0,
                    'balance' => 0,
                ]
            );

            if ($toBalance->entitled === 0 && $staff->leaveGroup) {
                $ent = $staff->leaveGroup->entitlements->firstWhere('type', $type);
                if ($ent) {
                    $toBalance->entitled = $ent->days_entitled;
                }
            }

            $toBalance->adjusted += $days;
            $toBalance->balance = $toBalance->entitled - $toBalance->used + $toBalance->adjusted;
            $toBalance->save();
            $results[] = $toBalance;
        }

        return response()->json(['data' => $results]);
    }
}
