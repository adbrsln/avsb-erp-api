<?php

namespace App\Http\Controllers;

use App\Services\Payroll\SocsoCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocsoController extends Controller
{
    public function calculate(Request $request): JsonResponse
    {
        $body = $request->all();
        $salary = (float) ($body['salary'] ?? 0);

        if ($salary <= 0) {
            return response()->json(['error' => 'salary must be greater than 0'], 400);
        }

        $result = (new SocsoCalculator)->calculate($salary);

        return response()->json($result->toArray());
    }

    public function listTiers(Request $request): JsonResponse
    {
        $tiers = (new SocsoCalculator)->getAllTiers();

        return response()->json(['data' => $tiers]);
    }
}
