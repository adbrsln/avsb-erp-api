<?php

namespace App\Http\Controllers;

use App\Models\EPFSchedule;
use App\Services\Payroll\EPFCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EPFController extends Controller
{
    public function schedules(Request $request): JsonResponse
    {
        $schedules = EPFSchedule::all()->toArray();

        return response()->json(['data' => $schedules]);
    }

    public function calculate(Request $request): JsonResponse
    {
        $body = $request->all();

        $salary = (float) ($body['salary'] ?? 0);
        $citizenship = $body['citizenship'] ?? 'citizen';
        $isPr = (bool) ($body['is_pr'] ?? false);
        $electedBefore1998 = (bool) ($body['elected_before_1998'] ?? false);
        $dateOfBirth = $body['date_of_birth'] ?? '2000-01-01';

        if ($salary <= 0) {
            return response()->json(['error' => 'salary must be greater than 0'], 400);
        }

        $result = (new EPFCalculator)->calculateRaw($salary, $citizenship, $isPr, $electedBefore1998, $dateOfBirth);

        return response()->json($result->toArray());
    }
}
