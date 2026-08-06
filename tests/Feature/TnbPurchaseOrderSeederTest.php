<?php

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\ClientPIC;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\JournalEntry;
use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\ProjectSubcontractor;
use App\Models\Subcontractor;
use App\Models\SubcontractorClaim;
use App\Models\Vendor;
use Database\Seeders\TnbPurchaseOrderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    // TestDataSeeder provides COA 1001/1104/2101/4101/6101 — 1102 (Bank) missing, needed for payment JE
    ChartOfAccount::firstOrCreate(['code' => '1102'], ['code' => '1102', 'name' => 'Bank', 'type' => 'asset']);
    ChartOfAccount::firstOrCreate(['code' => '1104'], ['code' => '1104', 'name' => 'Accounts Receivable', 'type' => 'asset']);
    ChartOfAccount::firstOrCreate(['code' => '4101'], ['code' => '4101', 'name' => 'Revenue', 'type' => 'revenue']);
    ChartOfAccount::firstOrCreate(['code' => '5101'], ['code' => '5101', 'name' => 'Subcontracting Costs', 'type' => 'expense']);

    Client::firstOrCreate(
        ['client_code' => 'TNB'],
        ['company_name' => 'Tenaga Nasional Berhad']
    );
});

function writeTnbCsv(string $path, array $rows): void
{
    $header = ['IS_PROCEED', 'PO NUMBER', 'DATE', 'CLIENT', 'TNB_STATION', 'TNB_PIC', 'PO_AMOUNT', 'PROJECT_STATUS', 'PELARASAN', 'PO_CONFIRMATION', 'DATE_SE', 'INVOICE', 'INVOICE_DATE', 'PAYMENT_STATUS', 'DATE_PAID', 'TOTAL_PAID', 'SUBCON', 'SUBCON_FEE', 'MAINCON', 'MAINCON_FEE', 'DEDUCTION', 'BALANCE_PAYMENT', 'INV_AVSB', 'INV_SUBCON', 'INV_DATE', 'DATE _PAID', 'STATUS'];
    $lines = [implode(',', $header)];
    foreach ($rows as $row) {
        $lines[] = implode(',', $row);
    }
    file_put_contents($path, implode("\n", $lines)."\n");
}

/** Row order: proceed, po, date, client, station, pic, po_amt, proj_status, pelarasan, po_conf, date_se, invoice, inv_date, pay_status, date_paid, total_paid, subcon, subcon_fee, maincon, maincon_fee, deduction, balance, inv_avsb, inv_subcon, inv_date, date_paid_alt, status */
function tnbRow(array $overrides = []): array
{
    $base = ['TRUE', '42024474', '21/01/2025', 'TNB', 'SHAH ALAM', 'Ferdawatie', '28514.38', 'COMPLETED', '45703.88', '4001821575', '', '5001357595', '20/03/2025', 'PAID', '4/17/25', '45703.88', '', '', '', '', '', '', '', '', '', '', ''];

    return array_replace($base, $overrides);
}

describe('TnbPurchaseOrderSeeder', function () {
    uses(RefreshDatabase::class);
    it('skips rows where IS_PROCEED is false', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [array_replace(tnbRow(), [0 => 'FALSE'])]);

        (new TnbPurchaseOrderSeeder($path))->run();

        expect(Project::where('po_number', '42024474')->exists())->toBeFalse();
        expect(Invoice::count())->toBe(0);

        unlink($path);
    });

    it('creates a basic project and a paid legacy invoice with payment + journal entries', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [tnbRow()]);

        (new TnbPurchaseOrderSeeder($path))->run();

        $project = Project::where('po_number', '42024474')->first();
        expect($project)->not->toBeNull();
        expect($project->client_id)->toBe(Client::where('client_code', 'TNB')->first()->id);
        expect($project->budget_amount)->toBe(28514.38);
        expect($project->description)->toContain('"subcon_fee":0');
        expect($project->description)->toContain('"po_confirmation":"4001821575"');
        expect(ProjectGroup::where('name', 'SHAH ALAM')->exists())->toBeTrue();
        expect($project->phases()->count())->toBe(10); // 9 standard + 1 PO confirmation

        $invoice = Invoice::where('invoice_number', '5001357595')->first();
        expect($invoice)->not->toBeNull();
        expect($invoice->project_id)->toBe($project->id);
        expect($invoice->status)->toBe('paid');
        expect($invoice->source)->toBe('legacy');
        expect((float) $invoice->total)->toBe(45703.88);
        expect($invoice->legacy_paid_date->format('Y-m-d'))->toBe('2025-04-17');

        expect(InvoicePayment::where('invoice_id', $invoice->id)->sum('amount'))->toBe(45703.88);
        expect(JournalEntry::where('reference_id', $invoice->id)->count())->toBe(2);

        unlink($path);
    });

    it('uses PO_AMOUNT as the invoice total when PELARASAN is empty', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [array_replace(tnbRow(), [8 => ''])]);

        (new TnbPurchaseOrderSeeder($path))->run();

        $invoice = Invoice::where('invoice_number', '5001357595')->first();
        expect($invoice)->not->toBeNull();
        expect((float) $invoice->total)->toBe(28514.38);

        unlink($path);
    });

    it('pairs the invoice to an existing project by po_number without duplicating it', function () {
        $client = Client::where('client_code', 'TNB')->first();
        $existing = Project::create([
            'name' => 'Existing TNB job',
            'project_code' => 'AV-TNB-2501-0001',
            'client' => 'Tenaga Nasional Berhad',
            'client_id' => $client->id,
            'po_number' => '42024474',
            'location' => 'SHAH ALAM',
            'status' => 'active',
        ]);
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [tnbRow()]);

        (new TnbPurchaseOrderSeeder($path))->run();

        expect(Project::where('po_number', '42024474')->count())->toBe(1);
        expect(Invoice::where('invoice_number', '5001357595')->first()->project_id)->toBe($existing->id);

        unlink($path);
    });

    it('matches the TNB PIC by name and stores client_pic_id', function () {
        $client = Client::where('client_code', 'TNB')->first();
        $pic = ClientPIC::create(['client_id' => $client->id, 'name' => 'Ferdawatie']);
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [tnbRow()]);

        (new TnbPurchaseOrderSeeder($path))->run();

        expect(Project::where('po_number', '42024474')->first()->client_pic_id)->toBe($pic->id);

        unlink($path);
    });

    it('marks SE phase completed with DATE_SE and completes all prior standard phases', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [array_replace(tnbRow(), [10 => '15/04/2025'])]);

        (new TnbPurchaseOrderSeeder($path))->run();

        $project = Project::where('po_number', '42024474')->first();
        $phases = $project->phases()->orderBy('order')->get();
        // SE phase completed at date_se
        $se = $phases->firstWhere('name', 'SE');
        expect($se->status)->toBe('completed');
        expect($se->completed_at->format('Y-m-d'))->toBe('2025-04-15');
        // All phases before SE completed too
        foreach ($phases->where('order', '<', $se->order) as $phase) {
            expect($phase->status)->toBe('completed');
            expect($phase->completed_at)->not->toBeNull();
        }

        unlink($path);
    });

    it('creates a completed phase from PO_CONFIRMATION when DATE_SE is present', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [array_replace(tnbRow(), [10 => '15/04/2025'])]);

        (new TnbPurchaseOrderSeeder($path))->run();

        $phase = Phase::where('project_id', Project::where('po_number', '42024474')->first()->id)
            ->where('name', 'PO Confirmation')->first();
        expect($phase)->not->toBeNull();
        expect($phase->status)->toBe('completed');
        expect($phase->completed_at->format('Y-m-d'))->toBe('2025-04-15');
        expect($phase->completion_remarks)->toContain('4001821575');

        unlink($path);
    });

    it('creates a pending phase when DATE_SE is empty', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [tnbRow()]);

        (new TnbPurchaseOrderSeeder($path))->run();

        $phase = Phase::where('project_id', Project::where('po_number', '42024474')->first()->id)
            ->where('name', 'PO Confirmation')->first();
        expect($phase)->not->toBeNull();
        expect($phase->status)->toBe('pending');
        expect($phase->completed_at)->toBeNull();

        unlink($path);
    });

    it('pairs INV_AVSB to an existing invoice and records the payment', function () {
        $client = Client::where('client_code', 'TNB')->first();
        $existing = Project::create([
            'name' => 'Existing TNB job',
            'project_code' => 'AV-TNB-2501-0001',
            'client' => 'Tenaga Nasional Berhad',
            'client_id' => $client->id,
            'po_number' => '42024474',
            'location' => 'SHAH ALAM',
            'status' => 'active',
        ]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-SYS-9001',
            'client' => 'Tenaga Nasional Berhad',
            'date' => '2025-03-20',
            'status' => 'draft',
            'subtotal' => 45703.88,
            'sst' => 0,
            'retention' => 0,
            'total' => 45703.88,
        ]);
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [array_replace(tnbRow(), [11 => '', 22 => 'INV-SYS-9001'])]);

        (new TnbPurchaseOrderSeeder($path))->run();

        $invoice->refresh();
        expect($invoice->project_id)->toBe($existing->id);
        expect($invoice->status)->toBe('paid');
        expect(InvoicePayment::where('invoice_id', $invoice->id)->sum('amount'))->toBe(45703.88);
        expect(JournalEntry::where('reference_type', 'payment')->where('reference_id', $invoice->id)->count())->toBe(1);

        unlink($path);
    });

    it('creates a legacy invoice from INV_AVSB when the invoice does not exist', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [array_replace(tnbRow(), [11 => '', 22 => 'AV/RC/2609'])]);

        (new TnbPurchaseOrderSeeder($path))->run();

        $invoice = Invoice::where('invoice_number', 'AV/RC/2609')->first();
        expect($invoice)->not->toBeNull();
        expect($invoice->source)->toBe('legacy');
        expect((float) $invoice->total)->toBe(45703.88);
        expect($invoice->items[0]['description'])->toContain('PO 42024474');
        expect($invoice->status)->toBe('paid');
        expect($invoice->project_id)->toBe(Project::where('po_number', '42024474')->first()->id);
        expect(InvoicePayment::where('invoice_id', $invoice->id)->sum('amount'))->toBe(45703.88);

        unlink($path);
    });

    it('shares the same invoice when INV_AVSB is owned by another project', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [
            array_replace(tnbRow(), [11 => '', 22 => 'AV/RC/2609']),
            array_replace(tnbRow(), [1 => '42028079', 11 => '', 22 => 'AV/RC/2609']),
        ]);

        (new TnbPurchaseOrderSeeder($path))->run();
        (new TnbPurchaseOrderSeeder($path))->run(); // idempotent re-run

        // One shared document — no duplicate invoice created
        expect(Invoice::count())->toBe(1);
        $invoice = Invoice::where('invoice_number', 'AV/RC/2609')->first();
        expect($invoice)->not->toBeNull();
        expect($invoice->status)->toBe('paid');
        // Each PO is a line item; invoice total = sum of items
        expect(count($invoice->items))->toBe(2);
        expect((float) $invoice->total)->toBe(91407.76); // 45703.88 x 2 items
        expect($invoice->items[1]['description'])->toContain('PO 42028079');
        // One invoice attached to both projects via pivot
        expect($invoice->projects()->pluck('project_id')->all())->toHaveCount(2);
        $second = Project::where('po_number', '42028079')->first();
        expect($second->sharedInvoices()->pluck('invoice_id')->all())->toBe([$invoice->id]);
        // Single payment covering the FULL invoice total
        $payment = InvoicePayment::where('invoice_id', $invoice->id)->first();
        expect($payment)->not->toBeNull();
        expect((float) $payment->amount)->toBe(91407.76);
        // Payment JE marks the invoice paid in full
        $paymentJe = JournalEntry::where('reference_type', 'payment')->where('reference_id', $invoice->id)->first();
        expect($paymentJe)->not->toBeNull();
        expect((float) $paymentJe->lines()->where('debit', '>', 0)->first()->debit)->toBe(91407.76);
        expect((float) $paymentJe->lines()->where('credit', '>', 0)->first()->credit)->toBe(91407.76);

        unlink($path);
    });

    it('creates subcon bill + payment JE when SUBCON present', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [array_replace(tnbRow(), [16 => 'Elektron Berkat', 17 => '25%', 20 => '19727.07', 23 => 'EB1523/2026'])]);

        (new TnbPurchaseOrderSeeder($path))->run();

        $sub = Subcontractor::where('subcontractor_code', 'EB')->first();
        expect($sub)->not->toBeNull();
        expect($sub->company_name)->toBe('Elektron Berkat');
        expect(Vendor::where('vendor_code', 'EB')->first())->not->toBeNull();

        $bill = Bill::where('bill_number', 'EB1523/2026')->first();
        expect($bill)->not->toBeNull();
        expect((float) $bill->total)->toBe(19727.07);
        expect($bill->status)->toBe('paid');

        $pay = BillPayment::where('bill_id', $bill->id)->first();
        expect((float) $pay->amount)->toBe(19727.07);
        expect(JournalEntry::where('reference_type', 'bill')->where('reference_id', $bill->id)->exists())->toBeTrue();
        expect(JournalEntry::where('reference_type', 'bill_payment')->where('reference_id', $bill->id)->exists())->toBeTrue();
        $ps = ProjectSubcontractor::where('project_id', Project::where('po_number', '42024474')->first()->id)->first();
        expect($ps)->not->toBeNull();
        expect($ps->status)->toBe('completed');

        $claim = SubcontractorClaim::where('claim_number', 'EB1523/2026')->first();
        expect($claim)->not->toBeNull();
        expect((float) $claim->claimed_amount)->toBe(19727.07);
        expect((float) $claim->net_payable)->toBe(19727.07);
        expect($claim->status)->toBe('paid');
        expect($claim->paid_at)->not->toBeNull();
        expect($claim->payment_reference)->toBe('EB1523/2026');

        unlink($path);
    });

    it('does not duplicate subcon bill on re-run', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        $row = [array_replace(tnbRow(), [16 => 'Elektron Berkat', 17 => '25%', 20 => '19727.07', 23 => 'EB1523/2026'])];
        writeTnbCsv($path, $row);

        (new TnbPurchaseOrderSeeder($path))->run();
        (new TnbPurchaseOrderSeeder($path))->run();

        expect(Bill::where('bill_number', 'EB1523/2026')->count())->toBe(1);
        expect(BillPayment::count())->toBe(1);
        expect(SubcontractorClaim::count())->toBe(1);

        unlink($path);
    });

    it('is idempotent on re-run — no duplicate project, invoice, phase, or payment', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [tnbRow()]);

        (new TnbPurchaseOrderSeeder($path))->run();
        (new TnbPurchaseOrderSeeder($path))->run();

        expect(Project::where('po_number', '42024474')->count())->toBe(1);
        expect(Invoice::where('invoice_number', '5001357595')->count())->toBe(1);
        expect(Phase::where('name', 'PO Confirmation')->count())->toBe(1);
        expect(Project::where('po_number', '42024474')->first()->phases()->count())->toBe(10);
        expect(InvoicePayment::count())->toBe(1);

        unlink($path);
    });

    it('auto-generates an invoice number when INVOICE and INV_AVSB are empty', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [array_replace(tnbRow(), [11 => ''])]);

        (new TnbPurchaseOrderSeeder($path))->run();

        $invoice = Invoice::first();
        expect($invoice)->not->toBeNull();
        expect($invoice->invoice_number)->toStartWith('INV-');

        unlink($path);
    });

    it('backfills start_date on an existing project that is missing it', function () {
        $client = Client::where('client_code', 'TNB')->first();
        $existing = Project::create([
            'name' => 'Existing no-start job',
            'project_code' => 'AV-MNT-BACKFILL',
            'client' => 'Tenaga Nasional Berhad',
            'client_id' => $client->id,
            'po_number' => '42024474',
            'status' => 'active',
        ]);
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [tnbRow()]);

        (new TnbPurchaseOrderSeeder($path))->run();

        expect($existing->refresh()->start_date->format('Y-m-d'))->toBe('2025-01-21');

        unlink($path);
    });

    it('parses US-format dates (DATE and INVOICE_DATE) into project start and invoice date', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [array_replace(tnbRow(), [2 => '10/31/25', 12 => '6/23/26'])]);

        (new TnbPurchaseOrderSeeder($path))->run();

        $project = Project::where('po_number', '42024474')->first();
        expect($project->start_date->format('Y-m-d'))->toBe('2025-10-31');
        $invoice = Invoice::where('invoice_number', '5001357595')->first();
        expect($invoice->date->format('Y-m-d'))->toBe('2026-06-23');

        unlink($path);
    });

    it('does not duplicate auto-numbered invoices on re-run', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [array_replace(tnbRow(), [11 => ''])]);

        (new TnbPurchaseOrderSeeder($path))->run();
        (new TnbPurchaseOrderSeeder($path))->run();

        expect(Invoice::count())->toBe(1);

        unlink($path);
    });

    it('gracefully skips when the CSV file is missing', function () {
        (new TnbPurchaseOrderSeeder('/nonexistent/tnb-purchase-orders.csv'))->run();

        expect(Project::where('po_number', '42024474')->exists())->toBeFalse();
        expect(Invoice::count())->toBe(0);
    });
});

describe('app:import-tnb-purchase-orders command', function () {
    uses(RefreshDatabase::class);
    it('fails when the CSV file is missing', function () {
        $exit = Artisan::call('app:import-tnb-purchase-orders', [
            '--file' => '/nonexistent/tnb-purchase-orders.csv',
            '--force' => true,
        ]);

        expect($exit)->toBe(1);
        expect(Project::where('po_number', '42024474')->exists())->toBeFalse();
    });

    it('imports rows with --force', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [tnbRow()]);

        $exit = Artisan::call('app:import-tnb-purchase-orders', [
            '--file' => $path,
            '--force' => true,
        ]);

        expect($exit)->toBe(0);
        expect(Project::where('po_number', '42024474')->exists())->toBeTrue();
        expect(Invoice::where('invoice_number', '5001357595')->exists())->toBeTrue();

        unlink($path);
    });

    it('dry run modifies nothing', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [tnbRow()]);

        $exit = Artisan::call('app:import-tnb-purchase-orders', [
            '--file' => $path,
            '--dry-run' => true,
        ]);

        expect($exit)->toBe(0);
        expect(Project::where('po_number', '42024474')->exists())->toBeFalse();
        expect(Invoice::count())->toBe(0);

        unlink($path);
    });
});
