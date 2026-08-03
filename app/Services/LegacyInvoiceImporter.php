<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
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
     * @param  array{invoice_number?: string, project_code?: string, project_name?: string, client: string, amount: float, status: string, date?: string, due_date?: string, paid_date?: string}  $data
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

        $project = $this->resolveProject($data);
        $invoiceNumber = $this->resolveInvoiceNumber($data['invoice_number'] ?? '');

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'project_id' => $project?->id,
            'client' => $client,
            'date' => $data['date'] ?? date('Y-m-d'),
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
            'legacy_paid_date' => ($status === 'paid' && ! empty($data['paid_date'])) ? $data['paid_date'] : null,
        ]);

        if ($file !== null) {
            $this->storeDocument($invoice, $file);
        }

        return $invoice;
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
