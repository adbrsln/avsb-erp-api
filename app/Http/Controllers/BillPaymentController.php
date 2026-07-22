<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\NumberingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillPaymentController extends Controller
{
    public function index(Request $request, int $id): JsonResponse
    {
        $bill = Bill::findOrFail($id);
        $payments = BillPayment::where('bill_id', $bill->id)
            ->with('debitAccount', 'creditAccount')
            ->orderByDesc('payment_date')
            ->get();

        return response()->json(['data' => $payments]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $bill = Bill::findOrFail($id);
        $data = $request->all();

        if ($bill->status === 'paid') {
            return response()->json(['error' => 'Bill is already paid'], 422);
        }

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            return response()->json(['error' => 'Amount must be greater than 0'], 422);
        }

        if ($amount > $bill->balance) {
            return response()->json(['error' => 'Payment amount exceeds outstanding balance'], 422);
        }

        $paymentDate = $data['payment_date'] ?? Carbon::now()->format('Y-m-d');
        $debitAccountId = (int) ($data['debit_account_id'] ?? 0);
        $creditAccountId = (int) ($data['credit_account_id'] ?? 0);

        if (! $debitAccountId || ! $creditAccountId) {
            return response()->json(['error' => 'debit_account_id and credit_account_id are required'], 422);
        }

        DB::beginTransaction();
        try {
            $payment = BillPayment::create([
                'bill_id' => $bill->id,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'debit_account_id' => $debitAccountId,
                'credit_account_id' => $creditAccountId,
                'payment_reference' => $data['payment_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $apAccount = ChartOfAccount::where('code', '2101')->first();

            if ($apAccount) {
                $je = JournalEntry::create([
                    'entry_number' => (new NumberingService)->generate('journal'),
                    'entry_date' => $paymentDate,
                    'description' => 'Bill payment - '.($bill->bill_number ?? ''),
                    'reference_type' => 'bill_payment',
                    'reference_id' => $bill->id,
                    'status' => 'posted',
                    'posted_at' => Carbon::now(),
                ]);

                JournalEntryLine::create([
                    'journal_entry_id' => $je->id,
                    'account_id' => $apAccount->id,
                    'debit' => $amount,
                    'description' => $bill->bill_number.' - '.($data['payment_reference'] ?? 'payment'),
                ]);

                JournalEntryLine::create([
                    'journal_entry_id' => $je->id,
                    'account_id' => $creditAccountId,
                    'credit' => $amount,
                    'description' => $bill->bill_number.' - '.($data['payment_reference'] ?? 'payment'),
                ]);
            }

            $newPaidAmount = round($bill->paid_amount + $amount, 2);
            $newBalance = round($bill->total - $newPaidAmount, 2);
            $newStatus = $newBalance <= 0 ? 'paid' : ($bill->status === 'unpaid' ? 'partially_paid' : $bill->status);

            $bill->update([
                'paid_amount' => $newPaidAmount,
                'balance' => $newBalance,
                'status' => $newStatus,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->error('Bill payment failed', ['bill_id' => $bill->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Payment failed: '.$e->getMessage()], 500);
        }

        $payment->load('debitAccount', 'creditAccount');

        return response()->json($payment, 201);
    }
}
