<?php

namespace App\Http\Controllers;

use App\Models\PayRun;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayRunController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $params = $request->all();
        $query = PayRun::with('staff');

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        $errors = [];
        if (empty($data['staff_id'])) {
            $errors[] = 'staff_id is required';
        }
        if (empty($data['period_start'])) {
            $errors[] = 'period_start is required';
        }
        if (empty($data['period_end'])) {
            $errors[] = 'period_end is required';
        }
        if (! empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        if (isset($data['gross_pay'])) {
            $data['gross_pay'] = (float) $data['gross_pay'];
        } else {
            $data['gross_pay'] = 0;
        }
        if (isset($data['deductions'])) {
            $data['deductions'] = (float) $data['deductions'];
        } else {
            $data['deductions'] = 0;
        }
        if (isset($data['total_hours'])) {
            $data['total_hours'] = (float) $data['total_hours'];
        }
        if (isset($data['hourly_rate'])) {
            $data['hourly_rate'] = (float) $data['hourly_rate'];
        }

        $data['net_pay'] = $data['gross_pay'] - $data['deductions'];
        $data['status'] = $data['status'] ?? 'pending';
        $data['pay_run_number'] = (new NumberingService)->generate('pay_run');

        $item = PayRun::create(fillableData(new PayRun, $data));
        $item->load('staff');

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = PayRun::with('staff')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = PayRun::findOrFail($id);
        $data = $request->all();

        if (isset($data['gross_pay'])) {
            $data['gross_pay'] = (float) $data['gross_pay'];
        }
        if (isset($data['deductions'])) {
            $data['deductions'] = (float) $data['deductions'];
        }

        $gross = $data['gross_pay'] ?? $item->gross_pay;
        $deductions = $data['deductions'] ?? $item->deductions;
        $data['net_pay'] = (float) $gross - (float) $deductions;

        $item->update(fillableData($item, $data));
        $item->load('staff');

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        PayRun::findOrFail($id)->delete();

        return response()->noContent();
    }

    public function markPaid(Request $request, int $id): JsonResponse
    {
        $item = PayRun::findOrFail($id);

        if ($item->status === 'paid') {
            return response()->json(['errors' => ['Pay run is already paid']], 422);
        }

        $item->update(['status' => 'paid', 'paid_at' => Carbon::now()]);
        $item->load('staff');

        return response()->json($item);
    }
}
