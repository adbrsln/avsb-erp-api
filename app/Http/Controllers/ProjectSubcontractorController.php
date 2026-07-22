<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Project;
use App\Models\ProjectSubcontractor;
use App\Models\StaffProfile;
use App\Services\NumberingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectSubcontractorController extends Controller
{
    private function getStaffId(Request $request): ?int
    {
        $user = $request->user();
        if (! $user || ! $user->email) {
            return null;
        }

        $staff = StaffProfile::where('email', $user->email)->first();

        return $staff ? (int) $staff->id : null;
    }

    public function index(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $items = ProjectSubcontractor::with('subcontractor', 'claims')
            ->where('project_id', $project->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $data = $request->all();

        if (empty($data['subcontractor_id'])) {
            return response()->json(['error' => 'subcontractor_id is required'], 422);
        }

        $assignment = ProjectSubcontractor::create([
            'project_id' => $project->id,
            'subcontractor_id' => $data['subcontractor_id'],
            'scope_of_work' => $data['scope_of_work'] ?? null,
            'contract_value' => $data['contract_value'] ?? 0,
            'retention_pct' => $data['retention_pct'] ?? 0,
            'retention_amount' => 0,
            'retention_released_at_cc' => 0,
            'retention_released_at_dlp' => 0,
            'dlp_end_date' => $data['dlp_end_date'] ?? null,
            'cc_date' => $data['cc_date'] ?? null,
            'status' => $data['status'] ?? 'active',
            'assigned_by' => $this->getStaffId($request),
        ]);

        $assignment->load('subcontractor', 'claims', 'project');

        return response()->json($assignment, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $assignment = ProjectSubcontractor::with('subcontractor', 'claims')
            ->findOrFail($id);

        return response()->json($assignment);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $assignment = ProjectSubcontractor::findOrFail($id);

        $assignment->update(fillableData($assignment, $data));
        $assignment->load('subcontractor', 'claims');

        return response()->json($assignment);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $assignment = ProjectSubcontractor::with('claims')->findOrFail($id);

        $hasBlockingClaims = $assignment->claims()
            ->whereIn('status', ['approved', 'paid'])
            ->exists();

        if ($hasBlockingClaims) {
            return response()->json([
                'error' => 'Cannot remove assignment with approved or paid claims.',
            ], 422);
        }

        $assignment->claims()->delete();
        $assignment->delete();

        return response()->json(null, 204);
    }

    public function releaseRetention(Request $request, int $id): JsonResponse
    {
        $assignment = ProjectSubcontractor::findOrFail($id);
        $data = $request->all();

        $amount = (float) ($data['amount'] ?? 0);
        $stage = $data['stage'] ?? null;

        if ($amount <= 0) {
            return response()->json(['error' => 'Amount must be greater than 0'], 422);
        }

        if (! in_array($stage, ['cc', 'dlp'])) {
            return response()->json(['error' => 'Stage must be cc or dlp'], 422);
        }

        if ($stage === 'cc') {
            $available = $assignment->retention_amount - $assignment->retention_released_at_cc;
            if ($amount > $available) {
                return response()->json(['error' => 'Amount exceeds available CC retention'], 422);
            }
            $assignment->increment('retention_released_at_cc', $amount);
        } else {
            $available = $assignment->retention_amount - $assignment->retention_released_at_dlp;
            if ($amount > $available) {
                return response()->json(['error' => 'Amount exceeds available DLP retention'], 422);
            }
            $assignment->increment('retention_released_at_dlp', $amount);
        }

        $retentionAccount = ChartOfAccount::where('code', '2109')->first();
        $bankAccount = ChartOfAccount::where('code', '1102')->first();

        if ($retentionAccount && $bankAccount) {
            $je = JournalEntry::create([
                'entry_number' => (new NumberingService)->generate('journal'),
                'entry_date' => Carbon::now()->format('Y-m-d'),
                'description' => 'Retention release - '.strtoupper($stage).' - '.($assignment->subcontractor?->company_name ?? 'Subcontractor'),
                'reference_type' => 'retention_release',
                'reference_id' => $assignment->id,
                'status' => 'posted',
                'posted_at' => Carbon::now(),
                'created_by' => $this->getStaffId($request),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id' => $retentionAccount->id,
                'debit' => $amount,
                'description' => 'Retention released - '.strtoupper($stage),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id' => $bankAccount->id,
                'credit' => $amount,
                'description' => 'Retention released - '.strtoupper($stage),
            ]);
        }

        $assignment->refresh()->load('subcontractor', 'claims');

        return response()->json($assignment);
    }
}
