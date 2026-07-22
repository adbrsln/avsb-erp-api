<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\NumberingService;
use App\Services\PeriodLockService;
use App\Traits\PaginatedResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    use PaginatedResponse;

    public function chartOfAccounts(Request $request): JsonResponse
    {
        $accounts = ChartOfAccount::orderBy('code')->get();

        return response()->json(['data' => $accounts]);
    }

    public function storeJournalEntry(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['entry_date']) || empty($data['lines']) || ! is_array($data['lines'])) {
            return response()->json(['error' => 'entry_date and lines are required'], 422);
        }

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($data['lines'] as $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);
            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return response()->json(['error' => 'Total debits must equal total credits'], 422);
        }

        $entryDate = $data['entry_date'];
        $lockError = PeriodLockService::assertOpen($entryDate);
        if ($lockError) {
            return response()->json(['error' => $lockError], 422);
        }

        $entry = JournalEntry::create([
            'entry_number' => (new NumberingService)->generate('journal'),
            'entry_date' => $data['entry_date'],
            'description' => $data['description'] ?? null,
            'reference_type' => 'manual',
            'status' => 'draft',
            'created_by' => $request->user()?->id,
        ]);

        foreach ($data['lines'] as $line) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $line['account_id'],
                'debit' => (float) ($line['debit'] ?? 0),
                'credit' => (float) ($line['credit'] ?? 0),
                'description' => $line['description'] ?? null,
            ]);
        }

        $entry->load('lines', 'lines.account');

        return response()->json($entry, 201);
    }

    public function listJournalEntries(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = JournalEntry::with('lines', 'lines.account')->orderBy('created_at', 'desc');

        return $this->paginate($query, $params);
    }

    public function getJournalEntry(Request $request, int $id): JsonResponse
    {
        $entry = JournalEntry::with('lines', 'lines.account')->findOrFail($id);

        return response()->json($entry);
    }

    public function postJournalEntry(Request $request, int $id): JsonResponse
    {
        $entry = JournalEntry::findOrFail($id);
        if ($entry->status === 'posted') {
            return response()->json(['error' => 'Journal entry already posted'], 422);
        }
        $entry->update(['status' => 'posted', 'posted_at' => Carbon::now()]);

        return response()->json($entry);
    }

    public function profitLoss(Request $request): JsonResponse
    {
        $params = $request->query();
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-t');

        $lines = JournalEntryLine::selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'posted')
                    ->whereBetween('entry_date', [$startDate, $endDate]);
            })
            ->groupBy('account_id')
            ->with('account')
            ->get();

        $income = [];
        $expenses = [];
        $totalIncome = 0;
        $totalExpenses = 0;

        foreach ($lines as $line) {
            if (! $line->account) {
                continue;
            }
            $type = $line->account->type;
            if ($type === 'income') {
                $netAmount = $line->total_credit - $line->total_debit;
                $income[] = ['account' => $line->account, 'amount' => $netAmount];
                $totalIncome += $netAmount;
            } elseif ($type === 'expense') {
                $netAmount = $line->total_debit - $line->total_credit;
                $expenses[] = ['account' => $line->account, 'amount' => $netAmount];
                $totalExpenses += $netAmount;
            }
        }

        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'income' => $income,
            'expenses' => $expenses,
            'total_income' => round($totalIncome, 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_profit' => round($totalIncome - $totalExpenses, 2),
        ]);
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $params = $request->query();
        $periodId = $params['period_id'] ?? null;

        $query = ChartOfAccount::with(['lines' => function ($q) use ($periodId) {
            $q->whereHas('journalEntry', function ($sub) use ($periodId) {
                $sub->where('status', 'posted');
                if ($periodId) {
                    $period = FiscalPeriod::find($periodId);
                    if ($period) {
                        $sub->whereBetween('entry_date', [$period->start_date->format('Y-m-d'), $period->end_date->format('Y-m-d')]);
                    }
                }
            });
        }])->orderBy('code')->get();

        $rows = $query->map(function ($acct) {
            $debit = $acct->lines->sum('debit');
            $credit = $acct->lines->sum('credit');

            return [
                'account' => $acct->only(['id', 'code', 'name', 'type']),
                'debit' => (float) $debit,
                'credit' => (float) $credit,
                'balance' => (float) ($debit - $credit),
            ];
        });

        $totalDr = (float) $rows->sum('debit');
        $totalCr = (float) $rows->sum('credit');

        return response()->json(['data' => $rows, 'total_debit' => $totalDr, 'total_credit' => $totalCr]);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $params = $request->query();
        $periodId = $params['period_id'] ?? null;

        $lines = JournalEntryLine::selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereHas('journalEntry', function ($q) use ($periodId) {
                $q->where('status', 'posted');
                if ($periodId) {
                    $period = FiscalPeriod::find($periodId);
                    if ($period) {
                        $q->where('entry_date', '<=', $period->end_date->format('Y-m-d'));
                    }
                }
            })
            ->groupBy('account_id')
            ->with('account')
            ->get();

        $grouped = [];
        foreach ($lines as $line) {
            if (! $line->account) {
                continue;
            }
            $type = $line->account->type;
            if (! isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $balance = $line->total_debit - $line->total_credit;
            $grouped[$type][] = [
                'account' => $line->account->only(['id', 'code', 'name', 'type']),
                'balance' => (float) $balance,
            ];
        }

        $assets = $grouped['asset'] ?? [];
        $liabilities = $grouped['liability'] ?? [];
        $equity = $grouped['equity'] ?? [];

        $totalAssets = (float) array_sum(array_column($assets, 'balance'));
        $totalLiabilities = (float) array_sum(array_column($liabilities, 'balance'));
        $totalEquity = (float) array_sum(array_column($equity, 'balance'));

        $result = [
            'assets' => $assets,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilities,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equity,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => round($totalLiabilities + $totalEquity, 2),
        ];

        if ($periodId) {
            $period = FiscalPeriod::find($periodId);
            if ($period) {
                $result['period'] = $period->only(['id', 'name', 'start_date', 'end_date', 'status']);
            }
        }

        return response()->json($result);
    }

    public function generalLedger(Request $request): JsonResponse
    {
        $params = $request->query();
        $accountId = $params['account_id'] ?? null;
        $periodId = $params['period_id'] ?? null;

        if (! $accountId) {
            return response()->json(['error' => 'account_id is required'], 422);
        }

        $account = ChartOfAccount::findOrFail($accountId);

        $query = JournalEntryLine::with(['journalEntry' => function ($q) {
            $q->select('id', 'entry_number', 'entry_date', 'description', 'status', 'reference_type');
        }])
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($periodId) {
                $q->where('status', 'posted');
                if ($periodId) {
                    $period = FiscalPeriod::find($periodId);
                    if ($period) {
                        $q->whereBetween('entry_date', [$period->start_date->format('Y-m-d'), $period->end_date->format('Y-m-d')]);
                    }
                }
            })
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entry_lines.id')
            ->select('journal_entry_lines.*');

        $openingBalance = 0.0;
        if ($periodId) {
            $period = FiscalPeriod::find($periodId);
            if ($period) {
                $ob = (float) (JournalEntryLine::where('account_id', $account->id)
                    ->whereHas('journalEntry', function ($q) use ($period) {
                        $q->where('status', 'posted')
                            ->where('entry_date', '<', $period->start_date->format('Y-m-d'));
                    })
                    ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
                    ->first()->balance ?? 0);
                $openingBalance = $ob;
            }
        }

        $runningBalance = $openingBalance;
        $lines = $query->get()->map(function ($line) use (&$runningBalance) {
            $dr = (float) $line->debit;
            $cr = (float) $line->credit;
            $runningBalance += ($dr - $cr);

            return [
                'id' => $line->id,
                'entry_id' => $line->journal_entry_id,
                'entry_number' => $line->journalEntry->entry_number ?? '',
                'entry_date' => $line->journalEntry->entry_date ?? '',
                'description' => $line->journalEntry->description ?? '',
                'reference_type' => $line->journalEntry->reference_type ?? '',
                'debit' => $dr,
                'credit' => $cr,
                'running_balance' => round($runningBalance, 2),
            ];
        });

        return response()->json([
            'account' => $account->only(['id', 'code', 'name', 'type']),
            'opening_balance' => $openingBalance,
            'ending_balance' => round($runningBalance, 2),
            'lines' => $lines,
        ]);
    }

    public function arAging(Request $request): JsonResponse
    {
        return $this->agingReport('1104', $request);
    }

    public function apAging(Request $request): JsonResponse
    {
        return $this->agingReport('2101', $request);
    }

    private function agingReport(string $accountCode, Request $request): JsonResponse
    {
        $account = ChartOfAccount::where('code', $accountCode)->firstOrFail();
        $asAt = $request->query('as_at', date('Y-m-d'));

        $lines = JournalEntryLine::selectRaw('journal_entry_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($asAt) {
                $q->where('status', 'posted')
                    ->where('entry_date', '<=', $asAt);
            })
            ->groupBy('journal_entry_id')
            ->with('journalEntry')
            ->get();

        $agingBuckets = ['current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, 'over_90' => 0];
        $total = 0;

        foreach ($lines as $line) {
            $balance = $line->total_debit - $line->total_credit;
            if (abs($balance) < 0.01) {
                continue;
            }
            $total += $balance;

            $entryDate = $line->journalEntry?->entry_date;
            if (! $entryDate) {
                continue;
            }
            $daysOverdue = (strtotime($asAt) - strtotime($entryDate)) / 86400;

            if ($daysOverdue <= 0) {
                $agingBuckets['current'] += $balance;
            } elseif ($daysOverdue <= 30) {
                $agingBuckets['1_30'] += $balance;
            } elseif ($daysOverdue <= 60) {
                $agingBuckets['31_60'] += $balance;
            } elseif ($daysOverdue <= 90) {
                $agingBuckets['61_90'] += $balance;
            } else {
                $agingBuckets['over_90'] += $balance;
            }
        }

        return response()->json([
            'as_at' => $asAt,
            'account' => $account,
            'aging' => $agingBuckets,
            'total' => round($total, 2),
            'entries' => $lines->map(function ($l) {
                return [
                    'entry_date' => $l->journalEntry?->entry_date,
                    'entry_number' => $l->journalEntry?->entry_number,
                    'description' => $l->journalEntry?->description,
                    'balance' => round($l->total_debit - $l->total_credit, 2),
                ];
            })->filter(fn ($l) => abs($l['balance']) >= 0.01)->values(),
        ]);
    }

    public function storeAccount(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['code']) || empty($data['name'])) {
            return response()->json(['error' => 'code and name are required'], 422);
        }

        if (ChartOfAccount::where('code', $data['code'])->exists()) {
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

    public function updateAccount(Request $request, int $id): JsonResponse
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

    public function destroyAccount(Request $request, int $id): JsonResponse
    {
        $account = ChartOfAccount::findOrFail($id);

        $referenced = JournalEntryLine::where('account_id', $account->id)->exists();
        if ($referenced) {
            return response()->json(['error' => 'Account is referenced by journal entry lines and cannot be deleted'], 422);
        }

        $account->delete();

        return response()->json(null, 204);
    }
}
