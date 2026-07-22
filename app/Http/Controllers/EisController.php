<?php

namespace App\Http\Controllers;

use App\Services\Payroll\EisCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EisController extends Controller
{
    public function calculate(Request $request): JsonResponse
    {
        $body = $request->all();
        $salary = (float) ($body['salary'] ?? 0);

        if ($salary <= 0) {
            return response()->json(['error' => 'salary must be greater than 0'], 400);
        }

        $result = (new EisCalculator)->calculate($salary);

        return response()->json($result->toArray());
    }

    public function listTiers(Request $request): JsonResponse
    {
        $tiers = (new EisCalculator)->getAllTiers();

        return response()->json(['data' => $tiers]);
    }
}
