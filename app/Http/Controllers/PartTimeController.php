<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\PayRun;
use App\Models\StaffProfile;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartTimeController extends Controller
{
    use PaginatedResponse;

    public function staff(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = StaffProfile::where('worker_status', 'part_time')
            ->where('is_active', true);

        return $this->paginate($query, $params);
    }

    public function hours(Request $request, int $staffId): JsonResponse
    {
        $params = $request->query();
        $dateFrom = $params['date_from'] ?? date('Y-m-01');
        $dateTo = $params['date_to'] ?? date('Y-m-t');

        $records = Attendance::where('staff_id', $staffId)
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->whereNotNull('clock_out')
            ->orderBy('date')
            ->get();

        $totalHours = round($records->sum('total_hours'), 2);
        $staff = StaffProfile::find($staffId);
        $rate = $staff->hourly_rate ?? 0;
        $grossPay = round($totalHours * $rate, 2);

        return response()->json([
            'staff_id' => $staffId,
            'staff_name' => $staff->name ?? 'Unknown',
            'hourly_rate' => $rate,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_hours' => $totalHours,
            'gross_pay' => $grossPay,
            'records' => $records->toArray(),
        ]);
    }

    public function pay(Request $request): JsonResponse
    {
        $body = $request->all();
        $staffId = (int) ($body['staff_id'] ?? 0);
        $dateFrom = $body['period_start'] ?? date('Y-m-01');
        $dateTo = $body['period_end'] ?? date('Y-m-t');
        $deductions = (float) ($body['deductions'] ?? 0);

        $staff = StaffProfile::find($staffId);
        if (! $staff) {
            return response()->json(['error' => 'Staff not found'], 404);
        }

        $records = Attendance::where('staff_id', $staffId)
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->whereNotNull('clock_out')
            ->get();

        $totalHours = round($records->sum('total_hours'), 2);
        $rate = $staff->hourly_rate ?? 0;
        $grossPay = round($totalHours * $rate, 2);
        $netPay = round($grossPay - $deductions, 2);

        $payRun = PayRun::create([
            'staff_id' => $staffId,
            'pay_run_number' => (new NumberingService)->generate('pay_run'),
            'period_start' => $dateFrom,
            'period_end' => $dateTo,
            'total_hours' => $totalHours,
            'hourly_rate' => $rate,
            'gross_pay' => $grossPay,
            'deductions' => $deductions,
            'net_pay' => $netPay,
            'status' => 'pending',
        ]);

        return response()->json($payRun->toArray(), 201);
    }
}
