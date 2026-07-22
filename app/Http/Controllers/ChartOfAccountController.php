<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $accounts = ChartOfAccount::orderBy('code')->get();

        return response()->json(['data' => $accounts]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['code']) || empty($data['name'])) {
            return response()->json(['error' => 'code and name are required'], 422);
        }

        $exists = ChartOfAccount::where('code', $data['code'])->exists();
        if ($exists) {
            return response()->json(['error' => 'Account code already exists'], 422);
        }

        $account = ChartOfAccount::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => $data['type'] ?? 'expense',
            'category' => $data['category'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_system' => false,
            'description' => $data['description'] ?? null,
        ]);

        return response()->json($account, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $account = ChartOfAccount::findOrFail($id);
        $data = $request->all();

        if ($account->is_system && isset($data['code']) && $data['code'] !== $account->code) {
            return response()->json(['error' => 'Cannot change code of a system account'], 422);
        }

        if (isset($data['code'])) {
            $duplicate = ChartOfAccount::where('code', $data['code'])->where('id', '!=', $account->id)->exists();
            if ($duplicate) {
                return response()->json(['error' => 'Account code already exists'], 422);
            }
        }

        $account->update(fillableData($account, $data));

        return response()->json($account);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $account = ChartOfAccount::findOrFail($id);

        $referenced = JournalEntryLine::where('account_id', $account->id)->exists();
        if ($referenced) {
            return response()->json(['error' => 'Account is referenced by journal entry lines and cannot be deleted'], 422);
        }

        $account->delete();

        return response()->json(null, 204);
    }

    public function usage(Request $request, int $id): JsonResponse
    {
        $account = ChartOfAccount::findOrFail($id);
        $params = $request->query();
        $asAt = $params['as_at'] ?? null;
        $periodId = $params['period_id'] ?? null;

        if ($periodId) {
            $period = FiscalPeriod::find($periodId);
            if ($period) {
                $asAt = $period->end_date->format('Y-m-d');
            }
        }

        $balanceQuery = JournalEntryLine::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($asAt) {
                $q->where('status', 'posted');
                if ($asAt) {
                    $q->where('entry_date', '<=', $asAt);
                }
            })
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
            ->first();

        $balance = (float) ($balanceQuery->balance ?? 0);

        $openingBalance = null;
        $periodActivity = null;

        if ($asAt && $periodId) {
            $period = FiscalPeriod::find($periodId);
            if ($period) {
                $dayBefore = Carbon::parse($period->start_date)->subDay()->format('Y-m-d');
                $obQuery = JournalEntryLine::where('account_id', $account->id)
                    ->whereHas('journalEntry', function ($q) use ($dayBefore) {
                        $q->where('status', 'posted')
                            ->where('entry_date', '<=', $dayBefore);
                    })
                    ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
                    ->first();
                $openingBalance = (float) ($obQuery->balance ?? 0);

                $periodActivity = (float) (JournalEntryLine::where('account_id', $account->id)
                    ->whereHas('journalEntry', function ($q) use ($period) {
                        $q->where('status', 'posted')
                            ->whereBetween('entry_date', [$period->start_date->format('Y-m-d'), $period->end_date->format('Y-m-d')]);
                    })
                    ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
                    ->first()->balance ?? 0);
            }
        }

        $recentQuery = JournalEntryLine::with(['journalEntry' => function ($q) {
            $q->select('id', 'entry_number', 'entry_date', 'description', 'status');
        }])
            ->where('account_id', $account->id)
            ->when($asAt, function ($q) use ($asAt) {
                $q->whereHas('journalEntry', function ($sub) use ($asAt) {
                    $sub->where('entry_date', '<=', $asAt);
                });
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($line) {
                return [
                    'entry_id' => $line->journal_entry_id,
                    'entry_number' => $line->journalEntry->entry_number ?? '',
                    'entry_date' => $line->journalEntry->entry_date ?? '',
                    'description' => $line->journalEntry->description ?? '',
                    'debit' => (float) $line->debit,
                    'credit' => (float) $line->credit,
                    'status' => $line->journalEntry->status ?? 'draft',
                ];
            });

        return response()->json([
            'account' => $account,
            'balance' => $balance,
            'balance_type' => $balance >= 0 ? 'debit' : 'credit',
            'opening_balance' => $openingBalance,
            'activity' => $periodActivity,
            'recent_entries' => $recentQuery,
        ]);
    }
}
