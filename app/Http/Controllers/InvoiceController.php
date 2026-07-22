<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectMaterialUsage;
use App\Models\Receipt;
use App\Models\TaxCode;
use App\Services\DocumentGenerator;
use App\Services\FileStorageService;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationService;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    use PaginatedResponse;

    private FileStorageService $storage;

    public function __construct()
    {
        $this->storage = new FileStorageService;
    }

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = Invoice::with('contract', 'project');

        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%{$s}%")
                    ->orWhere('client', 'like', "%{$s}%");
            });
        }
        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $countQuery = clone $query;
        $statuses = ['draft', 'unpaid', 'partially_paid', 'paid', 'overdue'];
        $counts = [];
        foreach ($statuses as $status) {
            $counts[$status] = (clone $countQuery)->where('status', $status)->count();
        }

        return $this->paginate($query, $params, [
            'counts' => $counts,
            'sortable' => ['invoice_number', 'client', 'date', 'due_date', 'total', 'status', 'created_at'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        $items = $data['items'] ?? [];
        [$subtotal, $sst] = $this->calculateSstFromItems($items);
        $retentionPct = (float) ($data['retention_pct'] ?? 0);
        $retention = round($subtotal * ($retentionPct / 100), 2);
        $total = round($subtotal + $sst - $retention, 2);

        $invoice = Invoice::create([
            'invoice_number' => (new NumberingService)->generate('invoice'),
            'project_id' => ! empty($data['project_id']) ? $data['project_id'] : null,
            'client' => $data['client'] ?? '',
            'date' => $data['date'] ?? date('Y-m-d'),
            'due_date' => $data['due_date'] ?? null,
            'status' => 'draft',
            'subtotal' => $subtotal,
            'sst' => $sst > 0 ? $sst : 0,
            'retention' => $retention > 0 ? $retention : 0,
            'total' => $total,
            'items' => array_map(function ($item) {
                return [
                    'description' => $item['description'] ?? '',
                    'unit' => $item['unit'] ?? 'Sqm',
                    'quantity' => $item['quantity'] ?? 0,
                    'unit_rate' => $item['unit_rate'] ?? 0,
                    'total' => round(($item['quantity'] ?? 0) * ($item['unit_rate'] ?? 0), 2),
                    'tax_code' => $item['tax_code'] ?? null,
                ];
            }, $items),
        ]);

        $this->syncClientBuyerFields($invoice, $data['client'] ?? '');
        $invoice->load('project');

        return response()->json($invoice, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = Invoice::with('contract', 'project')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $item = Invoice::findOrFail($id);

        if ($item->status === 'paid') {
            return response()->json(['error' => 'Cannot modify a paid invoice'], 422);
        }

        unset($data['invoice_number'], $data['status']);

        $items = $data['items'] ?? [];
        if (! empty($items) && is_array($items)) {
            [$subtotal, $sst] = $this->calculateSstFromItems($items);
            $data['subtotal'] = $subtotal;
            $data['sst'] = $sst;
            $retention = round($subtotal * ((float) ($data['retention_pct'] ?? ($item->retention ?? 0)) / 100), 2);
            $data['retention'] = $retention;
            $data['total'] = round($subtotal + $sst - $retention, 2);
        }

        $item->update(fillableData($item, $data));
        try {
            $this->storage->delete('documents/invoices/'.$item->id.'.pdf');
        } catch (\Throwable $e) {
        }

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = Invoice::findOrFail($id);
        if ($item->status !== 'draft') {
            return response()->json(['error' => 'Only draft invoices can be deleted'], 422);
        }
        $item->delete();

        return response()->json(null, 204);
    }

    private function calculateSstFromItems(array $items): array
    {
        $subtotal = 0;
        $sst = 0;
        $taxCodes = TaxCode::all()->keyBy('code');
        foreach ($items as $item) {
            $total = round(($item['quantity'] ?? 0) * ($item['unit_rate'] ?? 0), 2);
            $subtotal += $total;
            if (! empty($item['tax_code'])) {
                $tc = $taxCodes->get($item['tax_code']);
                if ($tc) {
                    $sst += $total * ((float) $tc->rate / 100);
                }
            }
        }

        return [round($subtotal, 2), round($sst, 2)];
    }

    private function syncMilestones(Invoice $item): void
    {
        if (! $item->contract_id) {
            return;
        }
        $contract = Contract::find($item->contract_id);
        if (! $contract) {
            return;
        }
        $milestones = is_array($contract->billing_milestones)
            ? $contract->billing_milestones
            : (json_decode($contract->billing_milestones ?? '[]', true) ?: []);
        $changed = false;
        foreach ($milestones as $idx => $m) {
            if (($m['status'] ?? '') === 'billed' && ($m['invoice_id'] ?? '') == $item->id) {
                $milestones[$idx]['status'] = 'paid';
                $milestones[$idx]['payment_invoice_id'] = $item->id;
                $changed = true;
            }
        }
        if ($changed) {
            $contract->billing_milestones = $milestones;
            $contract->save();

            $allPaid = ! collect($milestones)->contains(fn ($m) => $m['status'] !== 'paid');
            if ($allPaid && $contract->status === 'active') {
                $contract->status = 'completed';
                $contract->save();
            }
        }
    }

    private function createReceipt(Invoice $invoice, InvoicePayment $payment): Receipt
    {
        $receipt = Receipt::create([
            'receipt_number' => (new NumberingService)->generate('receipt'),
            'invoice_id' => $invoice->id,
            'invoice_payment_id' => $payment->id,
            'amount' => $payment->amount,
            'date' => $payment->payment_date ?? date('Y-m-d'),
        ]);

        try {
            $pdf = (new DocumentGenerator)->receipt($receipt);
            $this->storage->put('documents/receipts/'.$receipt->id.'.pdf', $pdf, 'application/pdf');
        } catch (\Throwable $e) {
            Log::error('Receipt PDF generation failed', ['receipt_id' => $receipt->id, 'error' => $e->getMessage()]);
        }

        if ($invoice->project_id) {
            try {
                $filename = $receipt->receipt_number.'.pdf';
                $projectPath = 'uploads/projects/'.$invoice->project_id.'/'.$filename;
                $this->storage->put($projectPath, $pdf ?? (new DocumentGenerator)->receipt($receipt), 'application/pdf');

                $doc = new ProjectDocument;
                $doc->fill([
                    'project_id' => $invoice->project_id,
                    'original_filename' => $filename,
                    'stored_filename' => $filename,
                    'mime_type' => 'application/pdf',
                    'file_size' => 0,
                    'category' => 'Receipt',
                ]);
                $doc->file_path = $projectPath;
                $doc->save();
            } catch (\Throwable $e) {
                Log::error('Failed to store receipt in project documents', ['receipt_id' => $receipt->id, 'error' => $e->getMessage()]);
            }
        }

        return $receipt;
    }

    private function syncClientBuyerFields(Invoice $invoice, string $clientName): void
    {
        if (empty($clientName)) {
            return;
        }
        $client = Client::where('company_name', $clientName)->first();
        if (! $client) {
            return;
        }

        $updates = [
            'client_id' => $client->id,
            'buyer_tin' => $client->tax_id,
            'buyer_reg_no' => $client->registration_no,
            'buyer_sst_reg_no' => $client->sst_reg_no,
            'buyer_type' => $client->buyer_type,
            'buyer_email' => $client->email,
            'contact_phone' => $client->contact_phone,
            'buyer_contact' => $client->billing_address ?: $client->address,
        ];
        $updates = array_filter($updates, fn ($v) => $v !== null && $v !== '');
        if (! empty($updates)) {
            $invoice->update($updates);
        }
    }

    private function doIssue(Invoice $item): Invoice
    {
        $locked = Invoice::lockForUpdate()->findOrFail($item->id);
        if ($locked->status !== 'draft') {
            throw new \RuntimeException('Only draft invoices can be issued');
        }

        $revenueAccount = ChartOfAccount::where('code', '4101')->first();
        $arAccount = ChartOfAccount::where('code', '1104')->first();
        $sstPayableAccount = ChartOfAccount::where('code', '2107')->first();
        $subtotal = (float) ($locked->subtotal ?? 0);
        $sst = (float) ($locked->sst ?? 0);
        $retentionAmt = (float) ($locked->retention ?? 0);
        $netAmount = round($subtotal + $sst - $retentionAmt, 2);

        if ($revenueAccount && $arAccount) {
            $je = JournalEntry::create([
                'entry_number' => (new NumberingService)->generate('journal'),
                'entry_date' => date('Y-m-d'),
                'description' => 'Invoice issued - '.($locked->invoice_number ?? ''),
                'reference_type' => 'invoice', 'reference_id' => $locked->id,
                'status' => 'posted', 'posted_at' => date('Y-m-d H:i:s'),
            ]);
            JournalEntryLine::create([
                'journal_entry_id' => $je->id, 'account_id' => $arAccount->id,
                'debit' => $netAmount, 'description' => $locked->invoice_number,
            ]);
            JournalEntryLine::create([
                'journal_entry_id' => $je->id, 'account_id' => $revenueAccount->id,
                'credit' => $subtotal, 'description' => $locked->invoice_number,
            ]);
            if ($sst > 0 && $sstPayableAccount) {
                JournalEntryLine::create([
                    'journal_entry_id' => $je->id, 'account_id' => $sstPayableAccount->id,
                    'credit' => $sst, 'description' => 'SST on '.$locked->invoice_number,
                ]);
            }
            if ($retentionAmt > 0) {
                $retentionPayableAccount = ChartOfAccount::where('code', '2108')->first();
                if ($retentionPayableAccount) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $je->id, 'account_id' => $retentionPayableAccount->id,
                        'credit' => $retentionAmt, 'description' => 'Retention on '.$locked->invoice_number,
                    ]);
                }
            }
        }

        $this->syncClientBuyerFields($locked, $locked->client ?? '');

        $locked->update(['status' => 'unpaid']);

        if ($locked->project_id) {
            try {
                $pdf = (new DocumentGenerator)->invoice($locked);
                $filename = 'invoice_'.$locked->invoice_number.'.pdf';
                $filePath = 'uploads/projects/'.$locked->project_id.'/'.$filename;
                $storage = new FileStorageService;
                $storage->put($filePath, $pdf, 'application/pdf');
                $doc = new ProjectDocument;
                $doc->fill([
                    'project_id' => $locked->project_id,
                    'original_filename' => $filename, 'stored_filename' => $filename,
                    'mime_type' => 'application/pdf', 'file_size' => strlen($pdf),
                    'category' => 'Invoice',
                ]);
                $doc->file_path = $filePath;
                $doc->save();
            } catch (\Throwable $e) {
                Log::error('Failed to store invoice PDF', ['invoice_id' => $locked->id, 'error' => $e->getMessage()]);
            }
        }

        return $locked->fresh();
    }

    public function issue(Request $request, int $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            $item = $this->doIssue(Invoice::lockForUpdate()->findOrFail($id));
            DB::commit();
        } catch (\RuntimeException $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice issue failed', ['invoice_id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to issue invoice'], 500);
        }

        try {
            $pm = null;
            if ($item->project_id) {
                $project = Project::with('projectManager')->find($item->project_id);
                $pm = $project?->projectManager;
            }
            $allRecipients = [];
            if ($pm) {
                $allRecipients[] = ['email' => $pm->email, 'name' => $pm->name];
            }

            $attachments = [];
            if ($item->project_id) {
                $attachments[] = [
                    'path' => 'uploads/projects/'.$item->project_id.'/invoice_'.$item->invoice_number.'.pdf',
                    'filename' => $item->invoice_number.'.pdf',
                    'mime' => 'application/pdf',
                ];
            }

            NotificationService::queueToMany(
                NotificationEvent::INVOICE_ISSUED,
                $allRecipients,
                [
                    'invoice_number' => $item->invoice_number ?? '',
                    'client' => $item->client ?? '',
                    'total' => number_format($item->total ?? 0, 2),
                    'due_date' => $item->due_date ?? '',
                    'url' => '/finance/invoices/'.$item->id,
                ],
                'App\\Models\\Invoice',
                $item->id,
                null,
                null,
                null,
                $attachments
            );
        } catch (\Throwable $e) {
            Log::error('Notification failed: invoice.issued', ['invoice_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function markPaid(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $item = Invoice::findOrFail($id);

        if ($item->status === 'draft') {
            try {
                DB::beginTransaction();
                $item = $this->doIssue($item);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Auto-issue failed during markPaid', ['invoice_id' => $item->id, 'error' => $e->getMessage()]);

                return response()->json(['error' => 'Failed to issue invoice before marking as paid'], 500);
            }
        }

        if ($item->status !== 'unpaid' && $item->status !== 'overdue') {
            if ($item->status === 'paid' && isset($data['payment_reference'])) {
                $item->update(['payment_reference' => $data['payment_reference']]);

                return response()->json($item);
            }

            return response()->json(['error' => 'Invoice is already '.$item->status], 422);
        }

        $totalDue = round(($item->subtotal ?? 0) + ($item->sst ?? 0) - ($item->retention ?? 0), 2);
        $previousPayments = (float) InvoicePayment::where('invoice_id', $item->id)->sum('amount');
        $remaining = round($totalDue - $previousPayments, 2);

        DB::beginTransaction();
        try {
            $item->update([
                'status' => 'paid',
                'processed_at' => date('Y-m-d H:i:s'),
                'payment_reference' => $data['payment_reference'] ?? null,
            ]);

            $this->syncMilestones($item);

            $bankAccount = ChartOfAccount::where('code', '1102')->first();
            $arAccount = ChartOfAccount::where('code', '1104')->first();
            if ($bankAccount && $arAccount && $remaining > 0) {
                $je = JournalEntry::create([
                    'entry_number' => (new NumberingService)->generate('journal'),
                    'entry_date' => date('Y-m-d'),
                    'description' => 'Payment received - '.($item->invoice_number ?? ''),
                    'reference_type' => 'payment',
                    'reference_id' => $item->id,
                    'status' => 'posted',
                    'posted_at' => date('Y-m-d H:i:s'),
                ]);
                JournalEntryLine::create([
                    'journal_entry_id' => $je->id,
                    'account_id' => $bankAccount->id,
                    'debit' => $remaining,
                    'description' => $item->invoice_number,
                ]);
                JournalEntryLine::create([
                    'journal_entry_id' => $je->id,
                    'account_id' => $arAccount->id,
                    'credit' => $remaining,
                    'description' => $item->invoice_number,
                ]);
            }

            $invPayment = InvoicePayment::create([
                'invoice_id' => $item->id,
                'amount' => $remaining,
                'payment_date' => date('Y-m-d'),
                'payment_reference' => $data['payment_reference'] ?? null,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice markPaid failed', ['invoice_id' => $item->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to mark invoice as paid: '.$e->getMessage()], 500);
        }

        try {
            $this->createReceipt($item, $invPayment);
        } catch (\Throwable $e) {
            Log::error('Receipt creation failed after markPaid', ['invoice_id' => $item->id, 'error' => $e->getMessage()]);
        }

        try {
            $pm = null;
            if ($item->project_id) {
                $project = Project::with('projectManager')->find($item->project_id);
                $pm = $project?->projectManager;
            }
            if ($pm) {
                NotificationService::queue(
                    NotificationEvent::INVOICE_PAID,
                    $pm->email,
                    $pm->name,
                    [
                        'invoice_number' => $item->invoice_number ?? '',
                        'client' => $item->client ?? '',
                        'total' => number_format($item->total ?? 0, 2),
                        'url' => '/finance/invoices/'.$item->id,
                    ],
                    'App\\Models\\Invoice',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Notification failed: invoice.paid', ['invoice_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function storePayment(Request $request, int $id): JsonResponse
    {
        $item = Invoice::findOrFail($id);
        $body = $request->all();

        $amount = (float) ($body['amount'] ?? 0);
        $paymentDate = $body['payment_date'] ?? date('Y-m-d');
        $debitAccountId = (int) ($body['debit_account_id'] ?? 0);
        $creditAccountId = (int) ($body['credit_account_id'] ?? 0);

        if ($amount <= 0) {
            return response()->json(['error' => 'Amount must be greater than 0'], 422);
        }

        if (! $debitAccountId || ! $creditAccountId) {
            return response()->json(['error' => 'Debit and credit accounts are required'], 422);
        }

        $totalDue = round(($item->subtotal ?? 0) + ($item->sst ?? 0) - ($item->retention ?? 0), 2);
        $previousPayments = (float) InvoicePayment::where('invoice_id', $item->id)->sum('amount');
        $remaining = round($totalDue - $previousPayments, 2);
        if ($amount > $remaining) {
            return response()->json(['error' => 'Amount exceeds remaining balance of RM '.number_format($remaining, 2)], 422);
        }

        DB::beginTransaction();
        try {
            $payment = InvoicePayment::create([
                'invoice_id' => $item->id,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'debit_account_id' => $debitAccountId,
                'credit_account_id' => $creditAccountId,
                'payment_reference' => $body['payment_reference'] ?? null,
                'notes' => $body['notes'] ?? null,
            ]);

            $je = JournalEntry::create([
                'entry_number' => (new NumberingService)->generate('journal'),
                'entry_date' => $paymentDate,
                'description' => 'Payment received - '.($item->invoice_number ?? '').' (partial)',
                'reference_type' => 'payment',
                'reference_id' => $payment->id,
                'status' => 'posted',
                'posted_at' => date('Y-m-d H:i:s'),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id' => $debitAccountId,
                'debit' => $amount,
                'description' => $item->invoice_number.' - '.($body['payment_reference'] ?? 'payment'),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id' => $creditAccountId,
                'credit' => $amount,
                'description' => $item->invoice_number.' - '.($body['payment_reference'] ?? 'payment'),
            ]);

            $allPayments = $previousPayments + $amount;

            if ($allPayments >= $totalDue) {
                $item->update(['status' => 'paid', 'processed_at' => date('Y-m-d H:i:s')]);
                $this->syncMilestones($item);
            } elseif ($item->status !== 'partially_paid') {
                $item->update(['status' => 'partially_paid', 'processed_at' => date('Y-m-d H:i:s')]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice storePayment failed', ['invoice_id' => $item->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to record payment: '.$e->getMessage()], 500);
        }

        try {
            $receipt = $this->createReceipt($item, $payment);
        } catch (\Throwable $e) {
            Log::error('Receipt creation failed after storePayment', ['invoice_id' => $item->id, 'error' => $e->getMessage()]);
        }

        try {
            $pm = null;
            if ($item->project_id) {
                $project = Project::with('projectManager')->find($item->project_id);
                $pm = $project?->projectManager;
            }
            if ($pm) {
                $totalDue = round(($item->subtotal ?? 0) + ($item->sst ?? 0) - ($item->retention ?? 0), 2);
                $remaining = round($totalDue - $allPayments, 2);
                NotificationService::queue(
                    NotificationEvent::INVOICE_PARTIAL_PAYMENT,
                    $pm->email,
                    $pm->name,
                    [
                        'invoice_number' => $item->invoice_number ?? '',
                        'amount' => number_format($amount, 2),
                        'remaining' => number_format($remaining, 2),
                        'url' => '/finance/invoices/'.$item->id,
                    ],
                    'App\\Models\\Invoice',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Notification failed: invoice.partial-payment', ['invoice_id' => $item->id, 'error' => $e->getMessage()]);
        }

        $payment->load('debitAccount', 'creditAccount');

        return response()->json($payment->toArray(), 201);
    }

    public function creditNote(Request $request, int $id): JsonResponse
    {
        $item = Invoice::findOrFail($id);
        if ($item->status === 'draft') {
            return response()->json(['error' => 'Cannot create credit note for a draft invoice'], 422);
        }

        $body = $request->all();
        $amount = (float) ($body['amount'] ?? 0);
        $reason = trim($body['reason'] ?? '');

        if ($amount <= 0) {
            return response()->json(['error' => 'Amount must be greater than 0'], 422);
        }
        if ($amount > ($item->subtotal + $item->sst - $item->retention)) {
            return response()->json(['error' => 'Credit amount exceeds invoice total'], 422);
        }
        if (! $reason) {
            return response()->json(['error' => 'Reason is required'], 422);
        }

        $creditItems = $item->items ?: [];
        if (! empty($creditItems)) {
            $creditItems = array_map(function ($i) {
                $i['total'] = -abs((float) ($i['total'] ?? 0));

                return $i;
            }, $creditItems);
        } else {
            $creditItems = [['description' => 'Credit Note: '.$reason, 'amount' => -$amount]];
        }

        $ratio = $amount / max(($item->subtotal + $item->sst - $item->retention), 0.01);
        $creditSubtotal = round($item->subtotal * $ratio, 2);
        $creditSst = round($item->sst * $ratio, 2);
        $creditRetention = round($item->retention * $ratio, 2);
        $creditTotal = round($creditSubtotal + $creditSst - $creditRetention, 2);

        $creditNote = Invoice::create([
            'invoice_number' => (new NumberingService)->generate('invoice'),
            'credit_note_for_id' => $item->id,
            'contract_id' => $item->contract_id,
            'project_id' => $item->project_id,
            'client' => $item->client,
            'date' => date('Y-m-d'),
            'due_date' => date('Y-m-d'),
            'status' => 'draft',
            'subtotal' => -$creditSubtotal,
            'sst' => -$creditSst,
            'retention' => -$creditRetention,
            'total' => -$creditTotal,
            'items' => $creditItems,
            'einvoice_type' => 'credit_note',
            'einvoice_notes' => $reason,
        ]);

        return response()->json($creditNote, 201);
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $item = Invoice::findOrFail($id);
        if ($item->status !== 'overdue') {
            return response()->json(['error' => 'Only overdue invoices can be restored'], 422);
        }
        $item->update(['status' => 'unpaid']);

        return response()->json($item);
    }

    public function revertToDraft(Request $request, int $id): JsonResponse
    {
        $item = Invoice::findOrFail($id);
        if (! in_array($item->status, ['unpaid', 'overdue'])) {
            return response()->json(['error' => 'Only unpaid or overdue invoices can be reverted to draft'], 422);
        }
        DB::transaction(function () use ($item) {
            JournalEntry::where('reference_type', 'invoice')->where('reference_id', $item->id)->delete();
            $item->update(['status' => 'draft']);
        });
        try {
            $this->storage->delete('documents/invoices/'.$item->id.'.pdf');
        } catch (\Throwable $e) {
        }

        return response()->json($item);
    }

    public function payments(Request $request, int $id): JsonResponse
    {
        $item = Invoice::findOrFail($id);
        $payments = InvoicePayment::where('invoice_id', $item->id)
            ->with('debitAccount', 'creditAccount')
            ->orderByDesc('payment_date')
            ->get()
            ->toArray();

        $totalPaid = array_sum(array_column($payments, 'amount'));
        $remaining = round(($item->subtotal ?? 0) + ($item->sst ?? 0) - ($item->retention ?? 0) - $totalPaid, 2);

        return response()->json([
            'data' => $payments,
            'total_paid' => round($totalPaid, 2),
            'remaining' => $remaining,
        ]);
    }

    public function generateForProject(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $existing = Invoice::where('project_id', $project->id)->first();
        if ($existing) {
            return response()->json(['error' => 'Invoice already exists for this project', 'invoice' => $existing], 422);
        }

        $body = $request->all();
        $marginPct = (float) ($body['margin_pct'] ?? 0);

        $costs = $this->calculateProjectCosts($project);
        $baseAmount = $costs['total_cost'] > 0 ? $costs['total_cost'] : (float) ($project->budget_amount ?? 0);
        $totalAmount = $baseAmount * (1 + $marginPct / 100);

        $clientName = $project->client ?? ($project->clientRelation->company_name ?? '');

        $items = [];

        if ($costs['materials'] > 0) {
            $items[] = ['description' => 'Materials', 'amount' => round($costs['materials'], 2)];
        }
        if ($costs['claims'] > 0) {
            $items[] = ['description' => 'Subcontractor / Progress Claims', 'amount' => round($costs['claims'], 2)];
        }
        if ($costs['labor'] > 0) {
            $items[] = ['description' => 'Part-Time Labour', 'amount' => round($costs['labor'], 2)];
        }

        if (empty($items)) {
            $items[] = ['description' => 'Project Completion - '.$project->name, 'amount' => round($totalAmount, 2)];
        }

        $invoice = Invoice::create([
            'invoice_number' => (new NumberingService)->generate('invoice'),
            'project_id' => $project->id,
            'client' => $clientName,
            'date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'status' => 'draft',
            'subtotal' => round($totalAmount, 2),
            'sst' => 0,
            'retention' => 0,
            'total' => round($totalAmount, 2),
            'items' => $items,
        ]);

        $this->syncClientBuyerFields($invoice, $clientName);
        $invoice->load('project');

        return response()->json($invoice, 201);
    }

    private function calculateProjectCosts(Project $project): array
    {
        $materials = (float) ProjectMaterialUsage::where('project_id', $project->id)->sum('total_cost');

        $claims = (float) $project->claims()
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        $labor = (float) Attendance::where('project_id', $project->id)
            ->whereNotNull('clock_out')
            ->join('staff_profiles', 'attendance.staff_id', '=', 'staff_profiles.id')
            ->selectRaw('COALESCE(SUM(attendance.total_hours * staff_profiles.hourly_rate), 0) as total')
            ->pluck('total')
            ->first();

        return [
            'materials' => $materials,
            'claims' => $claims,
            'labor' => $labor,
            'total_cost' => $materials + $claims + $labor,
        ];
    }

    public function download(Request $request, int $id): Response|JsonResponse
    {
        $i = Invoice::findOrFail($id);
        $filename = $i->invoice_number.'.pdf';
        $path = 'documents/invoices/'.$i->id.'.pdf';

        $pdf = (new DocumentGenerator)->invoice($i);
        $this->storage->put($path, $pdf, 'application/pdf');

        $url = $this->storage->getPresignedUrl($path, 5, $filename);
        if ($url) {
            return response()->json(['url' => $url, 'filename' => $filename]);
        }

        $pdf = $this->storage->get($path);
        if ($pdf === null) {
            return response()->json(['error' => 'PDF not found'], 404);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => strlen($pdf),
        ]);
    }
}
