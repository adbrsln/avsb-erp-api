<?php

namespace App\Http\Controllers;

use App\Models\StaffAllowance;
use App\Models\StaffProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffAllowanceController extends Controller
{
    private const VALIDATION = [
        'name' => 'required|string|max:100',
        'amount' => 'required|numeric|min:0',
        'statutory_type' => 'required|in:wages,additional,overtime,reimbursement',
        'effective_from' => 'nullable|date',
        'effective_to' => 'nullable|date',
    ];

    public function index(Request $request, int $staffId): JsonResponse
    {
        $staff = StaffProfile::findOrFail($staffId);

        return response()->json(['data' => $staff->allowances()->orderBy('id')->get()]);
    }

    public function store(Request $request, int $staffId): JsonResponse
    {
        $staff = StaffProfile::findOrFail($staffId);
        $data = $request->validate(self::VALIDATION);

        $allowance = $staff->allowances()->create($data);

        return response()->json(['data' => $allowance], 201);
    }

    public function update(Request $request, int $staffId, int $allowanceId): JsonResponse
    {
        $allowance = StaffAllowance::where('staff_id', $staffId)->findOrFail($allowanceId);
        $data = $request->validate(self::VALIDATION);

        $allowance->update($data);

        return response()->json(['data' => $allowance]);
    }

    public function destroy(Request $request, int $staffId, int $allowanceId): JsonResponse
    {
        $allowance = StaffAllowance::where('staff_id', $staffId)->findOrFail($allowanceId);
        $allowance->delete();

        return response()->json(['message' => 'Allowance deleted']);
    }
}
