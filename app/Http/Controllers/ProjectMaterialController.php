<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Project;
use App\Models\ProjectMaterialUsage;
use App\Models\StaffProfile;
use App\Traits\NotifiesProjectParticipants;
use App\Traits\ProjectAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectMaterialController extends Controller
{
    use NotifiesProjectParticipants;
    use ProjectAccess;

    public function index(Request $request, int $id): JsonResponse
    {
        if (! $this->isProjectMember($request, $id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $project = Project::findOrFail($id);
        $items = ProjectMaterialUsage::with('item', 'phase', 'task', 'creator')
            ->where('project_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $canViewCosts = $this->isPmPlus($request);
        $totalCost = $canViewCosts ? $items->sum('total_cost') : 0;

        $itemsResult = $items->map(function ($u) use ($canViewCosts) {
            $arr = $u->toArray();
            if (! $canViewCosts) {
                unset($arr['unit_cost'], $arr['total_cost']);
            }

            return $arr;
        });

        $meta = $canViewCosts
            ? ['total_cost' => $totalCost, 'budget_amount' => $project->budget_amount]
            : ['budget_amount' => null];

        return response()->json(['data' => $itemsResult, 'meta' => $meta]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        if (! $this->isProjectMember($request, $id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $project = Project::findOrFail($id);
        $data = $request->all();

        if (empty($data['item_id']) || empty($data['qty'])) {
            return response()->json(['error' => 'item_id and qty are required'], 422);
        }

        $item = InventoryItem::findOrFail($data['item_id']);
        $qty = (float) $data['qty'];

        if ($qty <= 0) {
            return response()->json(['error' => 'qty must be positive'], 422);
        }

        $user = $request->user();
        $email = is_object($user) ? ($user->email ?? '') : ($user['email'] ?? '');
        $staff = StaffProfile::where('email', $email)->first();
        $userId = $staff?->id;

        $unitCost = $item->unit_cost;
        $totalCost = r2($qty * $unitCost);

        DB::beginTransaction();
        try {
            $usage = ProjectMaterialUsage::create([
                'project_id' => $project->id,
                'phase_id' => $data['phase_id'] ?? null,
                'task_id' => $data['task_id'] ?? null,
                'item_id' => $item->id,
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            InventoryTransaction::create([
                'item_id' => $item->id,
                'type' => 'out',
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reference_type' => 'material_usage',
                'reference_id' => $usage->id,
                'notes' => "Issued to project {$project->name} ({$project->project_code})",
            ]);

            $item->decrement('stock_qty', $qty);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->error('Material issue failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to issue material: '.$e->getMessage()], 500);
        }

        $this->notifyProject(
            'material.issued',
            ['item_name' => $item->name, 'qty' => $qty, 'project_name' => $project->name],
            $project->id, 'App\\Models\\ProjectMaterialUsage', $usage->id,
            'Material Issued: '.$item->name,
            $qty.' x '.$item->name.' issued to project '.$project->name.'.',
            '/projects/'.$project->id
        );

        $usage->load('item', 'phase', 'task', 'creator');

        return response()->json($usage, 201);
    }

    public function destroy(Request $request, int $projectId, int $usageId): JsonResponse
    {
        if (! $this->isPmPlus($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $usage = ProjectMaterialUsage::with('item')
            ->where('project_id', $projectId)
            ->findOrFail($usageId);

        $item = $usage->item;
        $qty = $usage->qty;
        $unitCost = $usage->unit_cost;
        $totalCost = $usage->total_cost;

        InventoryTransaction::create([
            'item_id' => $item->id,
            'type' => 'in',
            'qty' => $qty,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'reference_type' => 'material_reversal',
            'reference_id' => $usage->id,
            'notes' => "Reversal: {$usage->notes}",
        ]);

        $item->increment('stock_qty', $qty);
        $usage->delete();

        return response()->noContent();
    }
}
