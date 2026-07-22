<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Receipt;
use App\Models\SelfBilledInvoice;
use App\Services\DocumentGenerator;
use App\Services\FileStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateDocumentPdfs extends Command
{
    protected $signature = 'cron:generate-document-pdfs';

    protected $description = 'Pre-generate and store PDFs for all issued documents';

    public function handle(DocumentGenerator $gen, FileStorageService $storage): int
    {
        $total = 0;
        $errors = 0;

        // Invoices
        $invoices = Invoice::whereIn('status', ['unpaid', 'partially_paid', 'paid', 'overdue'])->get();
        foreach ($invoices as $doc) {
            try {
                $pdf = $gen->invoice($doc);
                $storage->put('documents/invoices/'.$doc->id.'.pdf', $pdf, 'application/pdf');
                $total++;
            } catch (\Throwable $e) {
                Log::error('Cron PDF generation failed (invoice)', ['id' => $doc->id, 'error' => $e->getMessage()]);
                $errors++;
            }
        }

        // Quotations
        $quotations = Quotation::whereIn('status', ['sent', 'accepted', 'converted'])->get();
        foreach ($quotations as $doc) {
            try {
                $pdf = $gen->quotation($doc);
                $storage->put('documents/quotations/'.$doc->id.'.pdf', $pdf, 'application/pdf');
                $total++;
            } catch (\Throwable $e) {
                Log::error('Cron PDF generation failed (quotation)', ['id' => $doc->id, 'error' => $e->getMessage()]);
                $errors++;
            }
        }

        // Contracts
        $contracts = Contract::whereIn('status', ['active', 'completed'])->get();
        foreach ($contracts as $doc) {
            try {
                $pdf = $gen->contract($doc);
                $storage->put('documents/contracts/'.$doc->id.'.pdf', $pdf, 'application/pdf');
                $total++;
            } catch (\Throwable $e) {
                Log::error('Cron PDF generation failed (contract)', ['id' => $doc->id, 'error' => $e->getMessage()]);
                $errors++;
            }
        }

        // Self-billed invoices
        $selfBilled = SelfBilledInvoice::whereNotIn('status', ['draft'])->get();
        foreach ($selfBilled as $doc) {
            try {
                $pdf = $gen->selfBilled($doc);
                $storage->put('documents/self-billed/'.$doc->id.'.pdf', $pdf, 'application/pdf');
                $total++;
            } catch (\Throwable $e) {
                Log::error('Cron PDF generation failed (self_billed)', ['id' => $doc->id, 'error' => $e->getMessage()]);
                $errors++;
            }
        }

        // Receipts
        $receipts = Receipt::all();
        foreach ($receipts as $doc) {
            try {
                $pdf = $gen->receipt($doc);
                $storage->put('documents/receipts/'.$doc->id.'.pdf', $pdf, 'application/pdf');
                $total++;
            } catch (\Throwable $e) {
                Log::error('Cron PDF generation failed (receipt)', ['id' => $doc->id, 'error' => $e->getMessage()]);
                $errors++;
            }
        }

        $this->info("Generated {$total} PDF(s), {$errors} error(s).");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
