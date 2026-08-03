<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class LegacyInvoiceImporter
{
    public const ALLOWED_STATUSES = ['unpaid', 'partially_paid', 'paid'];

    private FileStorageService $storage;

    public function __construct(?FileStorageService $storage = null)
    {
        $this->storage = $storage ?? new FileStorageService;
    }

    /**
     * Import an invoice that was issued outside the system (migration from legacy process).
     *
     * @param  array{invoice_number?: string, project_id?: int|string, project_code?: string, project_name?: string, client: string, amount: float, status: string, amount_paid?: float, date?: string, due_date?: string, paid_date?: string}  $data
     * @param  UploadedFile|null  $file  Optional original invoice document
     */
    public function import(array $data, ?UploadedFile $file = null): Invoice
    {
        $client = trim($data['client'] ?? '');
        if ($client === '') {
            throw new RuntimeException('Client is required');
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new RuntimeException('Amount must be greater than 0');
        }

        $status = $data['status'] ?? 'unpaid';
        if (! in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new RuntimeException('Status must be one of: '.implode(', ', self::ALLOWED_STATUSES));
        }

        $amountPaid = $this->resolveAmountPaid($amount, $status, $data);
        $paidDate = $this->resolvePaidDate($data, $amountPaid);

        return DB::transaction(function () use ($data, $file, $client, $amount, $status, $amountPaid, $paidDate) {
            $project = $this->resolveProject($data);
            $invoiceNumber = $this->resolveInvoiceNumber($data['invoice_number'] ?? '');
            $invoiceDate = $data['date'] ?? date('Y-m-d');

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'project_id' => $project?->id,
                'client' => $client,
                'date' => $invoiceDate,
                'due_date' => $data['due_date'] ?? null,
                'status' => $status,
                'source' => 'legacy',
                'subtotal' => $amount,
                'sst' => 0,
                'retention' => 0,
                'total' => $amount,
                'items' => [
                    ['description' => 'Legacy invoice (migrated)', 'unit' => 'Lot', 'quantity' => 1, 'unit_rate' => $amount, 'total' => $amount],
                ],
                'legacy_paid_date' => $amountPaid > 0 ? $paidDate : null,
            ]);

            $this->createIssueJournalEntry($invoice, $invoiceDate);

            if ($amountPaid > 0) {
                $this->createPayment($invoice, $amountPaid, $paidDate);
            }

            if ($file !== null) {
                $this->storeDocument($invoice, $file);
            }

            return $invoice;
        });
    }

    private function resolveAmountPaid(float $amount, string $status, array $data): float
    {
        $provided = (float) ($data['amount_paid'] ?? 0);

        if ($status === 'paid') {
            return $provided > 0 ? round(min($provided, $amount), 2) : $amount;
        }

        if ($status === 'partially_paid') {
            if ($provided <= 0) {
                throw new RuntimeException('Amount paid is required for partially paid invoices');
            }
            if ($provided > $amount) {
                throw new RuntimeException('Amount paid cannot exceed the invoice amount');
            }

            return round($provided, 2);
        }

        return 0;
    }

    private function resolvePaidDate(array $data, float $amountPaid): ?string
    {
        if ($amountPaid <= 0) {
            return null;
        }

        return ! empty($data['paid_date']) ? $data['paid_date'] : ($data['date'] ?? date('Y-m-d'));
    }

    private function createIssueJournalEntry(Invoice $invoice, string $entryDate): void
    {
        $revenueAccount = ChartOfAccount::where('code', '4101')->first();
        $arAccount = ChartOfAccount::where('code', '1104')->first();

        if (! $revenueAccount || ! $arAccount) {
            Log::warning('Legacy invoice issue JE skipped — revenue or AR account not found', ['invoice_id' => $invoice->id]);

            return;
        }

        $amount = (float) $invoice->total;

        $je = JournalEntry::create([
            'entry_number' => (new NumberingService)->generate('journal'),
            'entry_date' => $entryDate,
            'description' => 'Legacy invoice imported - '.($invoice->invoice_number ?? ''),
            'reference_type' => 'invoice', 'reference_id' => $invoice->id,
            'status' => 'posted', 'posted_at' => date('Y-m-d H:i:s'),
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $je->id, 'account_id' => $arAccount->id,
            'debit' => $amount, 'description' => $invoice->invoice_number,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $je->id, 'account_id' => $revenueAccount->id,
            'credit' => $amount, 'description' => $invoice->invoice_number,
        ]);
    }

    private function createPayment(Invoice $invoice, float $amountPaid, ?string $paidDate): void
    {
        $bankAccount = ChartOfAccount::where('code', '1102')->first();
        $arAccount = ChartOfAccount::where('code', '1104')->first();

        if (! $bankAccount || ! $arAccount) {
            throw new RuntimeException('Cannot record payment — bank (1102) or AR (1104) account not configured');
        }

        $je = JournalEntry::create([
            'entry_number' => (new NumberingService)->generate('journal'),
            'entry_date' => $paidDate ?? date('Y-m-d'),
            'description' => 'Payment received - '.($invoice->invoice_number ?? ''),
            'reference_type' => 'payment',
            'reference_id' => $invoice->id,
            'status' => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id' => $bankAccount->id,
            'debit' => $amountPaid,
            'description' => $invoice->invoice_number,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id' => $arAccount->id,
            'credit' => $amountPaid,
            'description' => $invoice->invoice_number,
        ]);

        InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => $amountPaid,
            'payment_date' => $paidDate ?? date('Y-m-d'),
            'payment_reference' => 'LEGACY-'.$invoice->invoice_number,
            'debit_account_id' => $bankAccount->id,
            'credit_account_id' => $arAccount->id,
        ]);
    }

    private function resolveProject(array $data): ?Project
    {
        if (! empty($data['project_id'])) {
            $project = Project::find($data['project_id']);
            if ($project) {
                return $project;
            }
        }

        if (! empty($data['project_code'])) {
            $project = Project::where('project_code', $data['project_code'])->first();
            if ($project) {
                return $project;
            }
        }

        if (! empty($data['project_name'])) {
            $project = Project::where('name', $data['project_name'])->first();
            if ($project) {
                return $project;
            }
        }

        return null;
    }

    private function resolveInvoiceNumber(string $provided): string
    {
        $number = trim($provided);
        if ($number === '') {
            return (new NumberingService)->generate('invoice');
        }

        if (Invoice::withTrashed()->where('invoice_number', $number)->exists()) {
            throw new RuntimeException('Invoice number "'.$number.'" already exists');
        }

        return $number;
    }

    private function storeDocument(Invoice $invoice, UploadedFile $file): void
    {
        $error = FileStorageService::validateUpload($file);
        if ($error !== null) {
            throw new RuntimeException('Document upload failed: '.$error);
        }

        $path = 'documents/legacy-invoices/'.$invoice->id.'.pdf';
        $mime = $file->getClientMimeType() ?: 'application/pdf';

        try {
            $this->storage->putFromFile($path, $file->getPathname(), $mime);
            $invoice->update([
                'legacy_document_path' => $path,
                'legacy_document_filename' => $file->getClientOriginalName(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Legacy invoice document upload failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Failed to store document: '.$e->getMessage());
        }
    }
}
