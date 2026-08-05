<?php

namespace App\Console\Commands;

use Database\Seeders\TnbPurchaseOrderSeeder;
use Illuminate\Console\Command;

class ImportTnbPurchaseOrders extends Command
{
    protected $signature = 'app:import-tnb-purchase-orders
        {--file= : Path to CSV file (default: database/data/tnb-purchase-orders.csv)}
        {--dry-run : Preview only, no changes made}
        {--force : Skip confirmation prompt}';

    protected $description = 'Import TNB purchase order tracking CSV (projects, invoices, payments, phases)';

    public function handle(): int
    {
        $filePath = $this->option('file') ?: database_path('data/tnb-purchase-orders.csv');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return Command::FAILURE;
        }

        if (! $force && ! $dryRun) {
            $this->warn("This will import TNB purchase orders from: {$filePath}");
            if (! $this->confirm('Continue?')) {
                $this->info('Aborted.');

                return Command::SUCCESS;
            }
        }

        $label = $dryRun ? ' (DRY RUN — no changes will be made)' : '';
        $this->info("Importing TNB purchase orders from {$filePath}{$label}...\n");

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            $this->error("Failed to open file: {$filePath}");

            return Command::FAILURE;
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            $this->error('CSV file is empty');

            return Command::FAILURE;
        }
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        $cols = TnbPurchaseOrderSeeder::columnMap($headers);

        $seeder = new TnbPurchaseOrderSeeder;
        $rowNum = 1;
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (array_filter($row) === []) {
                $skipped++;

                continue;
            }

            $get = function (string $key) use ($row, $cols) {
                $index = $cols[$key] ?? null;

                return $index !== null ? trim($row[$index] ?? '') : '';
            };

            $poNumber = $get('po_number');
            $isProceed = strtoupper($get('is_proceed')) === 'TRUE';

            if ($dryRun) {
                if (! $isProceed) {
                    $skipped++;

                    continue;
                }
                $this->line("  Row {$rowNum}: PO {$poNumber} client={$get('client')} station={$get('tnb_station')} amount=".$get('pelarasan').' inv_avsb='.$get('inv_avsb').' invoice='.$get('invoice'));
                $imported++;

                continue;
            }

            if (! $isProceed) {
                $skipped++;

                continue;
            }

            if ($poNumber === '') {
                $this->line("  Row {$rowNum}: skipped (no PO number)");
                $skipped++;

                continue;
            }

            try {
                $seeder->processRow($row, $cols, $poNumber);
                $this->line("  Row {$rowNum}: PO {$poNumber} imported");
                $imported++;
            } catch (\RuntimeException $e) {
                $this->line("  Row {$rowNum}: PO {$poNumber} — skipped ({$e->getMessage()})");
                $skipped++;
            } catch (\Throwable $e) {
                $this->line("  Row {$rowNum}: PO {$poNumber} — ERROR: {$e->getMessage()}");
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
}
