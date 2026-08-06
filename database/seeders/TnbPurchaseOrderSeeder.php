<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientPIC;
use App\Models\Invoice;
use App\Models\Phase;
use App\Models\Project;
use App\Services\LegacyInvoiceImporter;
use App\Services\NumberingService;
use Database\Seeders\Concerns\CreatesStandardPhases;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TnbPurchaseOrderSeeder extends Seeder
{
    use CreatesStandardPhases;

    private const DEFAULT_CSV = __DIR__.'/../../database/data/tnb-purchase-orders.csv';

    private LegacyInvoiceImporter $importer;

    public function __construct(private ?string $csvPath = null)
    {
        $this->importer = new LegacyInvoiceImporter;
    }

    /**
     * Normalize header names to snake_case column keys.
     * First-wins: DATE_PAID (populated) wins over DATE _PAID (empty trailing column).
     *
     * @return array<string, int>
     */
    public static function columnMap(array $header): array
    {
        $cols = [];
        foreach ($header as $index => $name) {
            $key = strtolower(str_replace(' ', '_', trim($name)));
            if ($key !== '' && ! array_key_exists($key, $cols)) {
                $cols[$key] = $index;
            }
        }

        return $cols;
    }

    public function run(): void
    {
        $path = $this->csvPath ?? self::DEFAULT_CSV;
        if (! file_exists($path)) {
            echo "  [TnbPurchaseOrderSeeder] Skipped: {$path} not found\n";

            return;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            echo "  [TnbPurchaseOrderSeeder] ERROR: cannot open {$path}\n";

            return;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return;
        }
        // Strip UTF-8 BOM (Excel exports) from the first header cell
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $cols = self::columnMap($header);

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (array_filter($row) === []) {
                continue; // blank line
            }
            $get = $this->cellReader($row, $cols);

            if (strtoupper($get('is_proceed')) !== 'TRUE') {
                $skipped++;

                continue;
            }

            $poNumber = $get('po_number');
            if ($poNumber === '') {
                $errors++;

                continue;
            }

            try {
                $this->processRow($row, $cols, $poNumber);
                $imported++;
            } catch (RuntimeException $e) {
                $errors++;
                echo "  [TnbPurchaseOrderSeeder] ERROR row {$poNumber}: {$e->getMessage()}\n";
            }
        }

        fclose($handle);

        echo "  [TnbPurchaseOrderSeeder] Imported: {$imported}, Skipped: {$skipped}, Errors: {$errors}".PHP_EOL;
    }

    /**
     * Import one CSV row inside its own transaction.
     *
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $cols
     */
    public function processRow(array $row, array $cols, string $poNumber): void
    {
        DB::transaction(function () use ($row, $cols, $poNumber) {
            $get = $this->cellReader($row, $cols);

            $clientCode = $get('client');
            $client = Client::where('client_code', $clientCode)->first();
            if (! $client) {
                throw new RuntimeException('Client code "'.$clientCode.'" not found. Seed the client master first.');
            }

            $project = $this->resolveOrCreateProject($client, $get, $poNumber);
            $this->createConfirmationPhase($project, $get);

            $amountPaid = $this->toFloat($get('total_paid'));
            $paidDate = $this->parsePaidDate($get('date_paid'));
            $invoiceNumber = $get('invoice');
            $existingInvoiceNumber = $get('inv_avsb');

            if ($existingInvoiceNumber !== '' && Invoice::withTrashed()->where('invoice_number', $existingInvoiceNumber)->exists()) {
                $invoice = Invoice::withTrashed()->where('invoice_number', $existingInvoiceNumber)->first();
                $this->pairExistingInvoice($invoice, $project, $amountPaid, $paidDate, $poNumber);
            } else {
                // INV_AVSB value used as the invoice number; when it names an invoice
                // that does not exist yet, create it as a legacy invoice (same as INVOICE column).
                $this->createLegacyInvoice($client, $project, $get, $existingInvoiceNumber !== '' ? $existingInvoiceNumber : $invoiceNumber);
            }
        });
    }

    private function createLegacyInvoice(Client $client, Project $project, callable $get, string $invoiceNumber): void
    {
        if ($invoiceNumber === '') {
            // Auto-numbered rows: idempotent — skip when the project was already invoiced
            // (prevents duplicate legacy invoices on re-import runs).
            if (Invoice::where('project_id', $project->id)->where('source', 'legacy')->exists()) {
                return;
            }
        } elseif (Invoice::withTrashed()->where('invoice_number', $invoiceNumber)->exists()) {
            throw new RuntimeException('Invoice number "'.$invoiceNumber.'" already exists');
        }
        $pelarasan = $this->toFloat($get('pelarasan'));
        $amount = $pelarasan > 0 ? $pelarasan : $this->toFloat($get('po_amount'));
        $this->importer->import([
            'invoice_number' => $invoiceNumber,
            'project_id' => $project->id,
            'client' => $client->company_name,
            'amount' => $amount,
            'status' => 'paid',
            'amount_paid' => $this->toFloat($get('total_paid')),
            'date' => $this->parseDmyDate($get('invoice_date')),
            'paid_date' => $this->parsePaidDate($get('date_paid')),
        ]);
    }

    /** @param  array<int, string|null>  $row */
    private function cellReader(array $row, array $cols): callable
    {
        return function (string $key) use ($row, $cols) {
            $index = $cols[$key] ?? null;

            return $index !== null ? trim($row[$index] ?? '') : '';
        };
    }

    private function resolveOrCreateProject(Client $client, callable $get, string $poNumber): Project
    {
        $project = Project::where('po_number', $poNumber)->first();
        if ($project) {
            return $project;
        }

        $station = $get('tnb_station');
        $picName = $get('tnb_pic');
        $picId = null;
        if ($picName !== '') {
            $picId = ClientPIC::where('client_id', $client->id)->where('name', $picName)->value('id');
        }

        $extra = [
            'tnb_station' => $station,
            'po_confirmation' => $get('po_confirmation'),
            'po_amount' => $this->toFloat($get('po_amount')),
            'pelarasan' => $this->toFloat($get('pelarasan')),
            'date_se' => $get('date_se'),
            'subcon' => $get('subcon'),
            'subcon_fee' => $this->toFloat($get('subcon_fee')),
            'maincon' => $get('maincon'),
            'maincon_fee' => $this->toFloat($get('maincon_fee')),
            'deduction' => $this->toFloat($get('deduction')),
            'balance_payment' => $this->toFloat($get('balance_payment')),
            'inv_subcon' => $get('inv_subcon'),
            'inv_date' => $get('inv_date'),
        ];

        $project = Project::create([
            'name' => $station !== '' ? 'TNB '.$station : 'TNB PO '.$poNumber,
            'project_code' => (new NumberingService)->generateProject($client->client_code),
            'client' => $client->company_name,
            'client_id' => $client->id,
            'client_pic_id' => $picId,
            'po_number' => $poNumber,
            'location' => $station,
            'status' => strtolower($get('project_status')) ?: 'active',
            'budget_amount' => $this->toFloat($get('po_amount')),
            'start_date' => $this->parseDmyDate($get('date')),
            'description' => json_encode(array_filter($extra, fn ($v) => $v !== '' && $v !== null), JSON_UNESCAPED_UNICODE),
        ]);

        $this->createStandardPhases($project);

        return $project;
    }

    private function createConfirmationPhase(Project $project, callable $get): void
    {
        $name = $get('po_confirmation');
        if ($name === '') {
            return;
        }

        if (Phase::where('project_id', $project->id)->where('name', $name)->exists()) {
            return; // idempotent
        }

        $completedDate = $this->parseDmyDate($get('date_se'));
        $order = (int) Phase::where('project_id', $project->id)->max('order') + 1;

        Phase::create([
            'project_id' => $project->id,
            'name' => $name,
            'order' => $order ?: 1,
            'status' => $completedDate ? 'completed' : 'pending',
            'completed_at' => $completedDate ? $completedDate.' 17:00:00' : null,
            'completion_remarks' => $completedDate ? 'TNB PO confirmation' : null,
        ]);
    }

    private function pairExistingInvoice(Invoice $invoice, Project $project, float $amountPaid, ?string $paidDate, string $poNumber): void
    {
        $update = [];
        if (! $invoice->project_id) {
            $update['project_id'] = $project->id;
        }
        if (! $invoice->client_id) {
            $update['client_id'] = $project->client_id;
            $update['client'] = $project->client;
        }
        if ($invoice->status !== 'paid') {
            $update['status'] = 'paid';
            $update['processed_at'] = $paidDate ? $paidDate.' 00:00:00' : date('Y-m-d H:i:s');
        }
        if ($update !== []) {
            $invoice->update($update);
        }

        if ($amountPaid > 0) {
            $amount = round(min($amountPaid, (float) $invoice->total), 2);
            $this->importer->recordPayment($invoice, $amount, $paidDate, 'TNB-'.$poNumber);
        }
    }

    private function toFloat(string $value): float
    {
        return (float) str_replace(',', '', $value);
    }

    /** DATE, INVOICE_DATE, DATE_SE use dd/mm/yyyy (also accept dd.mm.yyyy). */
    private function parseDmyDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = trim($value);
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $value, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        return null;
    }

    /** DATE_PAID uses US m/d/yy (e.g. 4/17/25); also accept dd/mm/yyyy fallback. */
    private function parsePaidDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = trim($value);
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{2})$#', $value, $m)) {
            return '20'.$m[3].'-'.str_pad($m[1], 2, '0', STR_PAD_LEFT).'-'.str_pad($m[2], 2, '0', STR_PAD_LEFT);
        }

        return $this->parseDmyDate($value);
    }
}
