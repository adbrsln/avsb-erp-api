<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\LegacyInvoiceImporter;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ImportLegacyInvoices extends Command
{
    protected $signature = 'app:import-legacy-invoices
        {--file= : Path to CSV file (default: database/data/legacy-invoices-migration.csv)}
        {--dry-run : Preview only, no changes made}
        {--force : Skip confirmation prompt}
        {--missing-project=skip : Behavior when project not found: skip|fail (default: skip)}
        {--document-dir= : Optional directory for per-row PDF files}';

    protected $description = 'Import legacy invoices (issued outside the system) from CSV — migration from existing process';

    public function handle(): int
    {
        $filePath = $this->option('file') ?: database_path('data/legacy-invoices-migration.csv');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $missingProject = $this->option('missing-project') ?: 'skip';
        $documentDir = $this->option('document-dir');

        if ($missingProject !== 'skip' && $missingProject !== 'fail') {
            $this->error('--missing-project must be "skip" or "fail"');

            return Command::FAILURE;
        }

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return Command::FAILURE;
        }

        if (! $force && ! $dryRun) {
            $this->warn("This will import legacy invoices from: {$filePath}");
            if (! $this->confirm('Continue?')) {
                $this->info('Aborted.');

                return Command::SUCCESS;
            }
        }

        $label = $dryRun ? ' (DRY RUN — no changes will be made)' : '';
        $this->info("Importing legacy invoices from {$filePath}{$label}...\n");

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            $this->error("Failed to open file: {$filePath}");

            return Command::FAILURE;
        }

        $headers = fgetcsv($handle, 0, ',', '"', '\\');
        if ($headers === false) {
            $this->error('CSV file is empty');

            return Command::FAILURE;
        }
        $expectedCount = count($headers);

        $rowNum = 1;
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        $importer = new LegacyInvoiceImporter;

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $rowNum++;

            while (count($row) < $expectedCount) {
                $row[] = '';
            }

            $data = array_combine($headers, $row);

            $invoiceNumber = trim($data['invoice_number'] ?? '');
            $client = trim($data['client'] ?? '');
            $amount = trim($data['amount'] ?? '');
            $status = trim($data['status'] ?? 'unpaid');
            $projectCode = trim($data['project_code'] ?? '');
            $projectName = trim($data['project_name'] ?? '');

            if (empty($invoiceNumber) && empty($client) && $amount === '') {
                $this->line("  Row {$rowNum}: skipped (empty row)");
                $skipped++;

                continue;
            }

            if (empty($client)) {
                $this->line("  Row {$rowNum} ({$invoiceNumber}): skipped (no client)");
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line("  [DRY RUN] Row {$rowNum}: {$invoiceNumber} client={$client} amount={$amount} status={$status} amount_paid=".trim($data['amount_paid'] ?? '').' project_code='.$projectCode);
                $imported++;

                continue;
            }

            // Missing-project policy: resolve before importing so "fail" stops the row.
            $projectFound = $this->projectExists($projectCode, $projectName);
            if (! $projectFound && ($projectCode !== '' || $projectName !== '')) {
                if ($missingProject === 'fail') {
                    $this->line("  Row {$rowNum}: {$invoiceNumber} — ERROR: no matching project (code={$projectCode}, name={$projectName})");
                    $errors++;

                    continue;
                }

                $this->line("  Row {$rowNum}: {$invoiceNumber} — skipped (no matching project: {$projectCode})");
                $skipped++;

                continue;
            }

            try {
                $invoice = $importer->import([
                    'invoice_number' => $invoiceNumber,
                    'project_code' => $projectCode,
                    'project_name' => $projectName,
                    'client' => $client,
                    'amount' => (float) $amount,
                    'status' => $status,
                    'amount_paid' => trim($data['amount_paid'] ?? '') !== '' ? (float) $data['amount_paid'] : 0,
                    'date' => trim($data['date'] ?? '') ?: null,
                    'due_date' => trim($data['due_date'] ?? '') ?: null,
                    'paid_date' => trim($data['paid_date'] ?? '') ?: null,
                ], $this->resolveDocument($data, $documentDir));

                $projectNote = $invoice->project_id ? ' project_id='.$invoice->project_id : ' (no project)';
                $this->line("  Row {$rowNum}: {$invoice->invoice_number} client={$client} amount={$invoice->total} status={$invoice->status}{$projectNote}");
                $imported++;
            } catch (\RuntimeException $e) {
                $this->line("  Row {$rowNum}: {$invoiceNumber} — skipped ({$e->getMessage()})");
                $skipped++;
            } catch (\Throwable $e) {
                $this->line("  Row {$rowNum}: {$invoiceNumber} — ERROR: {$e->getMessage()}");
                $errors++;
            }
        }

        fclose($handle);

        $this->newLine();
        $this->info("Done. Imported: {$imported}, Skipped: {$skipped}, Errors: {$errors}");
        if ($dryRun) {
            $this->info('(Dry run — no data was modified)');
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function projectExists(string $projectCode, string $projectName): bool
    {
        if ($projectCode !== '') {
            if (Project::where('project_code', $projectCode)->exists()) {
                return true;
            }
        }
        if ($projectName !== '') {
            if (Project::where('name', $projectName)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function resolveDocument(array $data, ?string $documentDir): ?UploadedFile
    {
        $filename = trim($data['document'] ?? '');
        if ($filename === '' || ! $documentDir) {
            return null;
        }

        $path = rtrim($documentDir, '/').'/'.ltrim($filename, '/');
        if (! file_exists($path)) {
            Log::warning('Legacy invoice document not found in document-dir', ['path' => $path]);

            return null;
        }

        return new UploadedFile(
            $path,
            basename($filename),
            mime_content_type($path) ?: 'application/pdf',
            null,
            true
        );
    }
}
