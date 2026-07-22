<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractVariation;
use App\Models\StaffProfile;
use App\Services\NumberingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContractVariationController extends Controller
{
    public function index(Request $request, int $id): JsonResponse
    {
        $contract = Contract::findOrFail($id);
        $items = ContractVariation::where('contract_id', $contract->id)->orderByDesc('created_at')->get();
        $totalAmount = $items->sum('amount');

        return response()->json([
            'data' => $items,
            'total_variation_amount' => round($totalAmount, 2),
        ]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $contract = Contract::findOrFail($id);
        $data = $request->all();

        if (empty($data['description'])) {
            return response()->json(['error' => 'description is required'], 422);
        }

        $data['contract_id'] = $contract->id;
        $data['variation_number'] = (new NumberingService)->generate('variation_order');
        $data['amount'] = (float) ($data['amount'] ?? 0);
        $data['status'] = 'pending';

        $item = ContractVariation::create(fillableData(new ContractVariation, $data));

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id, int $vid): JsonResponse
    {
        $item = ContractVariation::where('contract_id', $id)->findOrFail($vid);

        return response()->json($item);
    }

    public function update(Request $request, int $id, int $vid): JsonResponse
    {
        $item = ContractVariation::where('contract_id', $id)->findOrFail($vid);
        if ($item->status !== 'pending') {
            return response()->json(['error' => 'Only pending variations can be edited'], 422);
        }

        $data = $request->all();
        $item->update([
            'description' => $data['description'] ?? $item->description,
            'amount' => (float) ($data['amount'] ?? $item->amount),
            'notes' => $data['notes'] ?? $item->notes,
        ]);

        return response()->json($item);
    }

    public function approve(Request $request, int $id, int $vid): JsonResponse
    {
        $item = ContractVariation::where('contract_id', $id)->findOrFail($vid);
        $data = $request->all();

        if ($item->status !== 'pending') {
            return response()->json(['error' => 'Variation is already '.$item->status], 422);
        }

        $newStatus = $data['status'] ?? 'approved';
        if (! in_array($newStatus, ['approved', 'rejected'])) {
            return response()->json(['error' => 'Status must be approved or rejected'], 422);
        }

        $user = $request->user();
        $email = $user->email ?? '';
        $staff = StaffProfile::where('email', $email)->first();

        $item->update([
            'status' => $newStatus,
            'approved_by' => $staff ? $staff->id : null,
            'approved_at' => date('Y-m-d H:i:s'),
            'notes' => $data['notes'] ?? $item->notes,
        ]);

        if ($newStatus === 'approved') {
            try {
                $contract = Contract::find($id);
                if ($contract) {
                    $oldTotal = $contract->total_amount ?? 0;
                    $contract->total_amount = round($oldTotal + $item->amount, 2);

                    if ($contract->subtotal > 0 && $oldTotal > 0) {
                        $ratio = $contract->subtotal / $oldTotal;
                        $contract->subtotal = round($contract->subtotal + ($item->amount * $ratio), 2);
                    }

                    $contract->save();
                }
            } catch (\Throwable $e) {
                Log::error('Failed to update contract total from variation', ['contract_id' => $id, 'error' => $e->getMessage()]);
            }
        }

        return response()->json($item);
    }

    public function reject(Request $request, int $id, int $vid): JsonResponse
    {
        $item = ContractVariation::where('contract_id', $id)->findOrFail($vid);

        if ($item->status !== 'pending') {
            return response()->json(['error' => 'Variation is already '.$item->status], 422);
        }

        $user = $request->user();
        $email = $user->email ?? '';
        $staff = StaffProfile::where('email', $email)->first();
        $data = $request->all();

        $item->update([
            'status' => 'rejected',
            'approved_by' => $staff ? $staff->id : null,
            'approved_at' => date('Y-m-d H:i:s'),
            'notes' => $data['notes'] ?? $item->notes,
        ]);

        return response()->json($item);
    }

    public function destroy(Request $request, int $id, int $vid): JsonResponse
    {
        $item = ContractVariation::where('contract_id', $id)->findOrFail($vid);
        if ($item->status !== 'pending') {
            return response()->json(['error' => 'Only pending variations can be deleted'], 422);
        }
        $item->delete();

        return response()->json(null, 204);
    }
}
