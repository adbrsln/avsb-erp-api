<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\BillPayment;
use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\ClientPIC;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\ProjectSubcontractor;
use App\Models\Subcontractor;
use App\Models\SubcontractorClaim;
use App\Models\Vendor;
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
            $this->createSubcontractorBilling($project, $get, $poNumber);

            $amountPaid = $this->toFloat($get('total_paid'));
            $paidDate = $this->parseFlexibleDate($get('date_paid'));
            $invoiceNumber = $get('invoice');
            $existingInvoiceNumber = $get('inv_avsb');

            if ($existingInvoiceNumber !== '') {
                $invoice = Invoice::withTrashed()->where('invoice_number', $existingInvoiceNumber)->first();
                if ($invoice) {
                    // Pair to the same document — shared INV_AVSB numbers reuse one invoice
                    // across projects. No duplicate invoice is created.
                    $rowPaid = strtoupper($get('payment_status')) === 'PAID';
                    $this->pairExistingInvoice($invoice, $project, $amountPaid, $paidDate, $poNumber, $this->invoiceAmount($get), $rowPaid);
                } else {
                    // INV_AVSB invoice does not exist yet — create it as a legacy invoice
                    // using the INV_AVSB value as the invoice number.
                    $this->createLegacyInvoice($client, $project, $get, $existingInvoiceNumber, $poNumber);
                }
            } else {
                $this->createLegacyInvoice($client, $project, $get, $invoiceNumber, $poNumber);
            }
        });
    }

    private function backfillPhaseStatusExtras(Project $project, callable $get): void
    {
        $phaseStatus = $get('phase_status');
        $remarks = $get('phase_status_remarks');
        if ($phaseStatus === '' && $remarks === '') {
            return;
        }

        $extra = json_decode((string) $project->description, true) ?: [];
        $extra['phase_status'] = $phaseStatus;
        $extra['phase_status_remarks'] = $remarks;
        $project->update(['description' => json_encode($extra, JSON_UNESCAPED_UNICODE)]);
    }

    /**
     * Subcon invoice = AP bill (industry practice). Creates the subcontractor
     * master (code EB), the project-subcontractor link, the bill from the subcon
     * (bill_number = INV_SUBCON value), the bill receive JE and the payment JE.
     * Subcon fee = DEDUCTION, else SUBCON_FEE percent of the invoice amount,
     * else numeric SUBCON_FEE.
     */
    private function createSubcontractorBilling(Project $project, callable $get, string $poNumber): void
    {
        $subconName = $get('subcon');
        if ($subconName === '') {
            return;
        }

        $fee = $this->subconFeeAmount($get);
        $invSubcon = $get('inv_subcon');
        if ($fee <= 0 || $invSubcon === '') {
            return; // nothing to bill without a fee or subcon invoice number
        }

        $subcon = Subcontractor::firstOrCreate(
            ['subcontractor_code' => 'EB'],
            ['company_name' => $subconName, 'status' => 'active']
        );
        $vendor = Vendor::firstOrCreate(
            ['vendor_code' => 'EB'],
            ['company_name' => $subconName, 'status' => 'active']
        );
        $projectSub = ProjectSubcontractor::firstOrCreate(
            ['project_id' => $project->id, 'subcontractor_id' => $subcon->id],
            [
                'scope_of_work' => 'TNB subcon - PO '.$poNumber,
                'contract_value' => $fee,
                'status' => 'active',
            ]
        );
        // Past project: the subcon claim is already completed and paid
        $projectSub->update(['status' => 'completed']);

        $billDate = $this->parseFlexibleDate($get('inv_date')) ?? $this->parseFlexibleDate($get('date_paid')) ?? date('Y-m-d');
        $paidAt = $this->parseFlexibleDate($get('date_paid')) ?? $billDate;
        $claimDate = $this->parseFlexibleDate($get('inv_date')) ?? $billDate;
        if (! SubcontractorClaim::where('claim_number', $invSubcon)->exists()) {
            SubcontractorClaim::create([
                'project_subcontractor_id' => $projectSub->id,
                'claim_number' => $invSubcon,
                'claim_date' => $claimDate,
                'work_done_pct' => 100,
                'cumulative_pct' => 100,
                'claimed_amount' => $fee,
                'retention_deducted' => 0,
                'net_payable' => $fee,
                'previous_paid' => 0,
                'current_due' => 0,
                'status' => 'paid',
                'submitted_at' => $paidAt.' 08:00:00',
                'verified_at' => $paidAt.' 12:00:00',
                'approved_at' => $paidAt.' 16:00:00',
                'paid_at' => $paidAt.' 17:00:00',
                'payment_reference' => $invSubcon,
                'notes' => 'PO '.$poNumber.' — imported from TNB tracker (completed & paid)',
            ]);
        }
        if (Bill::where('bill_number', $invSubcon)->exists()) {
            return; // idempotent — bill already imported
        }
        $bill = Bill::create([
            'bill_number' => $invSubcon,
            'vendor_id' => $vendor->id,
            'vendor_bill_no' => $invSubcon,
            'bill_date' => $billDate,
            'due_date' => date('Y-m-d', strtotime($billDate.' +30 days')),
            'status' => 'unpaid',
            'subtotal' => $fee,
            'tax' => 0,
            'total' => $fee,
            'paid_amount' => 0,
            'balance' => $fee,
        ]);
        $expenseAccount = ChartOfAccount::where('code', '5101')->first();
        BillItem::create([
            'bill_id' => $bill->id,
            'description' => 'PO '.$poNumber.' — '.$subconName,
            'unit' => 'Lot',
            'quantity' => 1,
            'unit_price' => $fee,
            'total' => $fee,
            'account_id' => $expenseAccount?->id,
        ]);

        $apAccount = ChartOfAccount::where('code', '2101')->first();
        if ($apAccount && $expenseAccount) {
            $je = JournalEntry::create([
                'entry_number' => (new NumberingService)->generate('journal'),
                'entry_date' => $billDate,
                'description' => 'Bill received - '.$invSubcon,
                'reference_type' => 'bill',
                'reference_id' => $bill->id,
                'status' => 'posted',
                'posted_at' => date('Y-m-d H:i:s'),
            ]);
            JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $expenseAccount->id, 'debit' => $fee, 'description' => 'PO '.$poNumber.' — '.$subconName]);
            JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $apAccount->id, 'credit' => $fee, 'description' => $invSubcon]);
        }

        $bankAccount = ChartOfAccount::where('code', '1102')->first();
        if ($apAccount && $bankAccount) {
            $paidDate = $this->parseFlexibleDate($get('date_paid')) ?? $billDate;
            BillPayment::create([
                'bill_id' => $bill->id,
                'amount' => $fee,
                'payment_date' => $paidDate,
                'debit_account_id' => $apAccount->id,
                'credit_account_id' => $bankAccount->id,
                'payment_reference' => $invSubcon,
            ]);
            $je = JournalEntry::create([
                'entry_number' => (new NumberingService)->generate('journal'),
                'entry_date' => $paidDate,
                'description' => 'Bill payment - '.$invSubcon,
                'reference_type' => 'bill_payment',
                'reference_id' => $bill->id,
                'status' => 'posted',
                'posted_at' => date('Y-m-d H:i:s'),
            ]);
            JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $apAccount->id, 'debit' => $fee, 'description' => $invSubcon]);
            JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $bankAccount->id, 'credit' => $fee, 'description' => $invSubcon]);

            $bill->update(['paid_amount' => $fee, 'balance' => 0, 'status' => 'paid']);
        }

    }

    /** Subcon fee: DEDUCTION if present, else SUBCON_FEE percent of invoice amount, else numeric fee. */
    private function subconFeeAmount(callable $get): float
    {
        $deduction = $this->toFloat($get('deduction'));
        if ($deduction > 0) {
            return $deduction;
        }

        $feeStr = $get('subcon_fee');
        if (str_ends_with($feeStr, '%')) {
            $pct = (float) rtrim($feeStr, '%') / 100;

            return round($this->invoiceAmount($get) * $pct, 2);
        }

        return $this->toFloat($feeStr);
    }

    private function createLegacyInvoice(Client $client, Project $project, callable $get, string $invoiceNumber, string $poNumber): void
    {
        if ($invoiceNumber === '') {
            // Auto-numbered rows: idempotent — skip when the project was already invoiced
            // (prevents duplicate legacy invoices on re-import runs).
            if (Invoice::where('project_id', $project->id)->where('source', 'legacy')->exists()) {
                return;
            }
        } elseif (Invoice::withTrashed()->where('invoice_number', $invoiceNumber)->exists()) {
            return; // idempotent — invoice already imported; keep other row records (billing, claim, phases)
        }
        $amount = $this->invoiceAmount($get);
        $invoice = $this->importer->import([
            'invoice_number' => $invoiceNumber,
            'project_id' => $project->id,
            'client' => $client->company_name,
            'amount' => $amount,
            'status' => 'paid',
            'amount_paid' => $this->toFloat($get('total_paid')),
            'date' => $this->parseFlexibleDate($get('invoice_date')),
            'paid_date' => $this->parseFlexibleDate($get('date_paid')),
        ]);
        // Uniform line-item format: each PO is an item of the invoice document
        $invoice->update([
            'items' => [
                ['description' => 'PO '.$poNumber.' — '.$project->name, 'unit' => 'Lot', 'quantity' => 1, 'unit_rate' => $amount, 'total' => $amount],
            ],
        ]);
        $invoice->projects()->syncWithoutDetaching([$project->id]);
    }

    private function invoiceAmount(callable $get): float
    {
        $pelarasan = $this->toFloat($get('pelarasan'));

        return $pelarasan > 0 ? $pelarasan : $this->toFloat($get('po_amount'));
    }

    /** @param  array<int, string|null>  $row */
    private function cellReader(array $row, array $cols): callable
    {
        return function (string $key) use ($row, $cols) {
            $index = $cols[$key] ?? null;

            return $index !== null ? trim($row[$index] ?? '') : '';
        };
    }

    private function createProjectGroup(string $projectGroup): ProjectGroup|int|null
    {
        $projectGroup = trim($projectGroup);
        if ($projectGroup === '') {
            return $projectGroup;
        }

        $pg = ProjectGroup::firstOrCreate(
            ['name' => $projectGroup],
            [
                'description' => 'TNB Station - '.$projectGroup,
                'color' => $this->randomColor(),
            ]
        );

        return $pg;
    }

    private function randomColor(): string
    {
        $r = random_int(0, 0x80);
        $g = random_int(0, 0x80);
        $b = random_int(0, 0x80);

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    private function resolveOrCreateProject(Client $client, callable $get, string $poNumber): Project
    {
        $project = Project::where('po_number', $poNumber)->first();
        if ($project) {
            // Backfill missing dates on previously-imported projects (e.g. US-format rows
            // that failed parsing on the first run).
            $rowStart = $this->parseFlexibleDate($get('date'));
            if (! $project->start_date && $rowStart) {
                $project->update(['start_date' => $rowStart]);
            }
            $this->applyStandardPhaseCompletion($project, $this->parseFlexibleDate($get('date_se')));
            $this->backfillPhaseStatusExtras($project, $get);
            $this->applyPhaseStatus($project, $get);

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
            'phase_status' => $get('phase_status'),
            'phase_status_remarks' => $get('phase_status_remarks'),
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
            'start_date' => $this->parseFlexibleDate($get('date')),
            'description' => json_encode(array_filter($extra, fn ($v) => $v !== '' && $v !== null), JSON_UNESCAPED_UNICODE),
        ]);

        $project->groups()->sync($station !== '' ? $this->createProjectGroup($station) : []);

        $this->createStandardPhases($project);
        $this->applyStandardPhaseCompletion($project, $this->parseFlexibleDate($get('date_se')));
        $this->applyPhaseStatus($project, $get);

        return $project;
    }

    /**
     * PHASE_STATUS names the project's current phase: it is marked in_progress
     * and all phases before it are completed.
     */
    private function applyPhaseStatus(Project $project, callable $get): void
    {
        $status = $get('phase_status');
        if ($status === '') {
            return;
        }

        $phases = $project->phases()->orderBy('order')->get();
        $target = $phases->firstWhere(function ($phase) use ($status) {
            $name = strtoupper((string) $phase->name);
            $value = strtoupper($status);

            return $name === $value || str_contains($name, $value);
        });
        if (! $target) {
            return;
        }

        $remarks = $get('phase_status_remarks');
        foreach ($phases as $phase) {
            if ($phase->id === $target->id) {
                $update = [
                    'status' => 'in_progress',
                    'started_at' => ($phase->start_date?->format('Y-m-d') ?? date('Y-m-d')).' 08:00:00',
                ];
                if ($remarks !== '') {
                    $update['description'] = $remarks;
                }
                $phase->update($update);
            } elseif ($phase->order < $target->order && $phase->status !== 'completed') {
                $phase->update([
                    'status' => 'completed',
                    'completed_at' => ($phase->end_date?->format('Y-m-d') ?? date('Y-m-d')).' 17:00:00',
                ]);
            }
        }
    }

    /**
     * When DATE_SE is present: mark the SE phase completed at that date and
     * complete all standard phases that precede it.
     */
    private function applyStandardPhaseCompletion(Project $project, ?string $dateSe): void
    {
        if ($dateSe === null) {
            return;
        }

        $phases = $project->phases()->orderBy('order')->get();
        $se = $phases->firstWhere(fn ($phase) => str_ends_with(strtoupper((string) $phase->name), '(SE)'));

        foreach ($phases as $phase) {
            if ($se && $phase->id === $se->id) {
                $phase->update(['status' => 'completed', 'completed_at' => $dateSe.' 17:00:00']);
            } elseif ($se && $phase->order < $se->order) {
                $phase->update([
                    'status' => 'completed',
                    'completed_at' => ($phase->end_date?->format('Y-m-d') ?? $dateSe).' 17:00:00',
                ]);
            }
        }
    }

    private function createConfirmationPhase(Project $project, callable $get): void
    {
        $name = $get('po_confirmation');
        if ($name === '') {
            return;
        }

        // Guard on the phase name we actually create ('PO Confirmation'), not the CSV value
        if (Phase::where('project_id', $project->id)->where('name', 'PO Confirmation')->exists()) {
            return; // idempotent
        }

        $completedDate = $this->parseFlexibleDate($get('date_se'));
        $order = (int) Phase::where('project_id', $project->id)->max('order') + 1;

        Phase::create([
            'project_id' => $project->id,
            'name' => 'PO Confirmation',
            'order' => $order ?: 1,
            'status' => $completedDate ? 'completed' : 'pending',
            'completed_at' => $completedDate ? $completedDate.' 17:00:00' : null,
            'completion_remarks' => $completedDate ? 'TNB PO confirmation : '.$name : null,
        ]);
    }

    private function pairExistingInvoice(Invoice $invoice, Project $project, float $amountPaid, ?string $paidDate, string $poNumber, float $itemAmount, bool $rowPaid): void
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

        // Shared INV_AVSB = one invoice document; each PO is a line item.
        $items = $invoice->items ?? [];
        if (! collect($items)->contains(fn ($it) => str_contains((string) ($it['description'] ?? ''), 'PO '.$poNumber))) {
            $items[] = [
                'description' => 'PO '.$poNumber.' — '.$project->name,
                'unit' => 'Lot',
                'quantity' => 1,
                'unit_rate' => $itemAmount,
                'total' => $itemAmount,
            ];
            $sum = round(array_sum(array_map(fn ($it) => (float) ($it['total'] ?? 0), $items)), 2);
            $update['items'] = $items;
            $update['subtotal'] = $sum;
            $update['total'] = $sum;
        }

        if ($update !== []) {
            $invoice->update($update);
        }

        // Attach this project to the shared document (owner + additional projects)
        $invoice->projects()->syncWithoutDetaching([$project->id]);

        if (! $rowPaid) {
            return; // unpaid PO row — no payment recorded
        }

        // Invoice fully paid when all PO rows are paid: single payment covers the
        // whole invoice total, and the payment JE marks the invoice paid in full.
        $total = (float) $invoice->total;
        $payment = InvoicePayment::where('invoice_id', $invoice->id)->first();
        if ($payment) {
            if ((float) $payment->amount !== $total) {
                $payment->update(['amount' => $total]);
                $this->updatePaymentJournalEntries($invoice, $total);
            }
        } else {
            $this->importer->recordPayment($invoice, $total, $paidDate, 'TNB-'.$poNumber);
        }
    }

    private function updatePaymentJournalEntries(Invoice $invoice, float $total): void
    {
        $paymentJes = JournalEntry::where('reference_type', 'payment')->where('reference_id', $invoice->id)->get();
        foreach ($paymentJes as $je) {
            JournalEntryLine::where('journal_entry_id', $je->id)->where('debit', '>', 0)->update(['debit' => $total]);
            JournalEntryLine::where('journal_entry_id', $je->id)->where('credit', '>', 0)->update(['credit' => $total]);
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

    /** Handles mixed formats: US m/d/yy when 2-digit year (e.g. 4/17/25, 10/31/25), else dd/mm/yyyy (or dd.mm.yyyy). */
    private function parseFlexibleDate(?string $value): ?string
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
