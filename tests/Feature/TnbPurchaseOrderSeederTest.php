<?php

use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\ClientPIC;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\JournalEntry;
use App\Models\Phase;
use App\Models\Project;
use Database\Seeders\TnbPurchaseOrderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    // TestDataSeeder provides COA 1001/1104/2101/4101/6101 — 1102 (Bank) missing, needed for payment JE
    ChartOfAccount::firstOrCreate(['code' => '1102'], ['code' => '1102', 'name' => 'Bank', 'type' => 'asset']);
    ChartOfAccount::firstOrCreate(['code' => '1104'], ['code' => '1104', 'name' => 'Accounts Receivable', 'type' => 'asset']);
    ChartOfAccount::firstOrCreate(['code' => '4101'], ['code' => '4101', 'name' => 'Revenue', 'type' => 'revenue']);

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

    it('creates a completed phase from PO_CONFIRMATION when DATE_SE is present', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [array_replace(tnbRow(), [10 => '15/04/2025'])]);

        (new TnbPurchaseOrderSeeder($path))->run();

        $phase = Phase::where('project_id', Project::where('po_number', '42024474')->first()->id)
            ->where('name', '4001821575')->first();
        expect($phase)->not->toBeNull();
        expect($phase->status)->toBe('completed');
        expect($phase->completed_at->format('Y-m-d'))->toBe('2025-04-15');

        unlink($path);
    });

    it('creates a pending phase when DATE_SE is empty', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [tnbRow()]);

        (new TnbPurchaseOrderSeeder($path))->run();

        $phase = Phase::where('project_id', Project::where('po_number', '42024474')->first()->id)
            ->where('name', '4001821575')->first();
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

    it('is idempotent on re-run — no duplicate project, invoice, phase, or payment', function () {
        $path = tempnam(sys_get_temp_dir(), 'tnb_');
        writeTnbCsv($path, [tnbRow()]);

        (new TnbPurchaseOrderSeeder($path))->run();
        (new TnbPurchaseOrderSeeder($path))->run();

        expect(Project::where('po_number', '42024474')->count())->toBe(1);
        expect(Invoice::where('invoice_number', '5001357595')->count())->toBe(1);
        expect(Phase::where('name', '4001821575')->count())->toBe(1);
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
