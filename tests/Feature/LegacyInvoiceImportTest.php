<?php

use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\artisan;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];

    // Payment JE needs a bank account (1104/4101 exist from TestDataSeeder)
    ChartOfAccount::firstOrCreate(
        ['code' => '1102'],
        ['name' => 'Maybank Current Account', 'type' => 'asset', 'category' => 'current_asset', 'is_active' => true]
    );

    $this->client = Client::first() ?? Client::create([
        'client_code' => 'TEST-CLT-IMP',
        'company_name' => 'Import Test Client Sdn Bhd',
        'buyer_type' => 'company',
        'email' => 'import@testclient.com',
    ]);

    $this->project = Project::first() ?? Project::create([
        'name' => 'Import Test Project',
        'project_code' => 'IMP-PRJ-001',
        'client' => $this->client->company_name,
        'status' => 'active',
        'budget_amount' => 100000,
    ]);
});

describe('Legacy invoice import endpoint', function () {

    it('imports a paid legacy invoice with issue + payment journal entries', function () {
        $response = postJson('/api/v1/invoices/import', [
            'client' => $this->client->company_name,
            'project_id' => $this->project->id,
            'invoice_number' => 'LEGACY-INV-001',
            'amount' => 125000.00,
            'status' => 'paid',
            'date' => '2025-06-30',
            'due_date' => '2025-07-30',
            'paid_date' => '2025-08-15',
        ], $this->headers);

        $response->assertStatus(201)
            ->assertJsonPath('invoice_number', 'LEGACY-INV-001')
            ->assertJsonPath('source', 'legacy')
            ->assertJsonPath('status', 'paid')
            ->assertJsonPath('subtotal', 125000)
            ->assertJsonPath('total', 125000)
            ->assertJsonPath('sst', 0)
            ->assertJsonPath('retention', 0)
            ->assertJsonPath('legacy_paid_date', '2025-08-15T00:00:00.000000Z')
            ->assertJsonPath('project_id', $this->project->id);

        $invoice = Invoice::where('invoice_number', 'LEGACY-INV-001')->first();
        expect($invoice)->not->toBeNull();

        // Issue JE: DR AR 1104 / CR Revenue 4101, dated invoice date
        $issueJe = JournalEntry::where('reference_type', 'invoice')
            ->where('reference_id', $invoice->id)
            ->first();
        expect($issueJe)->not->toBeNull();
        expect($issueJe->entry_date->toDateString())->toBe('2025-06-30');
        expect(JournalEntryLine::where('journal_entry_id', $issueJe->id)->count())->toBe(2);

        $arAccount = ChartOfAccount::where('code', '1104')->first();
        $revenueAccount = ChartOfAccount::where('code', '4101')->first();
        expect(JournalEntryLine::where('journal_entry_id', $issueJe->id)->where('account_id', $arAccount->id)->sum('debit'))->toBe(125000);
        expect(JournalEntryLine::where('journal_entry_id', $issueJe->id)->where('account_id', $revenueAccount->id)->sum('credit'))->toBe(125000);

        // Payment JE: DR Bank 1102 / CR AR 1104, dated paid date
        $payJe = JournalEntry::where('reference_type', 'payment')
            ->where('reference_id', $invoice->id)
            ->first();
        expect($payJe)->not->toBeNull();
        expect($payJe->entry_date->toDateString())->toBe('2025-08-15');

        $bankAccount = ChartOfAccount::where('code', '1102')->first();
        expect(JournalEntryLine::where('journal_entry_id', $payJe->id)->where('account_id', $bankAccount->id)->sum('debit'))->toBe(125000);
        expect(JournalEntryLine::where('journal_entry_id', $payJe->id)->where('account_id', $arAccount->id)->sum('credit'))->toBe(125000);

        // InvoicePayment row so payment history is accurate
        expect(InvoicePayment::where('invoice_id', $invoice->id)->sum('amount'))->toBe(125000);
    });

    it('creates issue JE only for unpaid invoices', function () {
        postJson('/api/v1/invoices/import', [
            'client' => $this->client->company_name,
            'invoice_number' => 'LEGACY-UNPAID-001',
            'amount' => 5000,
            'status' => 'unpaid',
            'date' => '2025-01-10',
        ], $this->headers)->assertStatus(201);

        $invoice = Invoice::where('invoice_number', 'LEGACY-UNPAID-001')->first();

        expect(JournalEntry::where('reference_type', 'invoice')->where('reference_id', $invoice->id)->exists())->toBeTrue();
        expect(JournalEntry::where('reference_type', 'payment')->where('reference_id', $invoice->id)->exists())->toBeFalse();
        expect(InvoicePayment::where('invoice_id', $invoice->id)->exists())->toBeFalse();
    });

    it('creates partial payment JE + InvoicePayment for partially_paid with amount_paid', function () {
        postJson('/api/v1/invoices/import', [
            'client' => $this->client->company_name,
            'invoice_number' => 'LEGACY-PARTIAL-001',
            'amount' => 80000,
            'status' => 'partially_paid',
            'amount_paid' => 30000,
            'date' => '2025-05-15',
            'paid_date' => '2025-06-01',
        ], $this->headers)->assertStatus(201);

        $invoice = Invoice::where('invoice_number', 'LEGACY-PARTIAL-001')->first();
        expect($invoice->status)->toBe('partially_paid');
        expect($invoice->legacy_paid_date)->not->toBeNull();

        expect(JournalEntry::where('reference_type', 'invoice')->where('reference_id', $invoice->id)->exists())->toBeTrue();

        $payJe = JournalEntry::where('reference_type', 'payment')->where('reference_id', $invoice->id)->first();
        expect($payJe)->not->toBeNull();
        expect($payJe->entry_date->toDateString())->toBe('2025-06-01');

        $bankAccount = ChartOfAccount::where('code', '1102')->first();
        expect(JournalEntryLine::where('journal_entry_id', $payJe->id)->where('account_id', $bankAccount->id)->sum('debit'))->toBe(30000);

        expect(InvoicePayment::where('invoice_id', $invoice->id)->sum('amount'))->toBe(30000);
    });

    it('rejects partial invoice without amount_paid and amount_paid over amount', function () {
        postJson('/api/v1/invoices/import', [
            'client' => $this->client->company_name,
            'amount' => 5000,
            'status' => 'partially_paid',
        ], $this->headers)
            ->assertStatus(422)
            ->assertJsonPath('error', 'Amount paid is required for partially paid invoices');

        postJson('/api/v1/invoices/import', [
            'client' => $this->client->company_name,
            'amount' => 5000,
            'status' => 'partially_paid',
            'amount_paid' => 6000,
        ], $this->headers)
            ->assertStatus(422)
            ->assertJsonPath('error', 'Amount paid cannot exceed the invoice amount');
    });

    it('rejects duplicate manual invoice number', function () {
        Invoice::create([
            'invoice_number' => 'LEGACY-DUP-001',
            'client' => $this->client->company_name,
            'date' => '2025-01-01',
            'status' => 'unpaid',
            'subtotal' => 100,
            'sst' => 0,
            'retention' => 0,
            'total' => 100,
            'source' => 'legacy',
        ]);

        postJson('/api/v1/invoices/import', [
            'client' => $this->client->company_name,
            'invoice_number' => 'LEGACY-DUP-001',
            'amount' => 500,
            'status' => 'unpaid',
        ], $this->headers)
            ->assertStatus(422)
            ->assertJsonPath('error', 'Invoice number "LEGACY-DUP-001" already exists');
    });

    it('auto-generates invoice number when blank', function () {
        $response = postJson('/api/v1/invoices/import', [
            'client' => $this->client->company_name,
            'amount' => 5000,
            'status' => 'unpaid',
        ], $this->headers);

        $response->assertStatus(201)
            ->assertJsonPath('source', 'legacy');

        $number = $response->json('invoice_number');
        expect($number)->not->toBeEmpty();
        expect(Invoice::where('invoice_number', $number)->exists())->toBeTrue();
    });

    it('rejects invalid status and zero amount', function () {
        postJson('/api/v1/invoices/import', [
            'client' => $this->client->company_name,
            'amount' => 100,
            'status' => 'draft',
        ], $this->headers)
            ->assertStatus(422);

        postJson('/api/v1/invoices/import', [
            'client' => $this->client->company_name,
            'amount' => 0,
            'status' => 'unpaid',
        ], $this->headers)
            ->assertStatus(422);
    });

    it('stores uploaded document and download serves the original', function () {
        $pdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n190\n%%EOF\n";
        $tempPdf = tempnam(sys_get_temp_dir(), 'legacy-doc').'.pdf';
        file_put_contents($tempPdf, $pdfContent);
        $file = new UploadedFile($tempPdf, 'invoice-original.pdf', 'application/pdf', null, true);

        $response = $this->call('POST', '/api/v1/invoices/import', [
            'client' => $this->client->company_name,
            'invoice_number' => 'LEGACY-PDF-001',
            'amount' => 10000,
            'status' => 'paid',
        ], [], ['document' => $file], ['HTTP_AUTHORIZATION' => 'Bearer '.$this->token]);

        $response->assertStatus(201);

        $invoice = Invoice::where('invoice_number', 'LEGACY-PDF-001')->first();
        expect($invoice->legacy_document_path)->not->toBeNull();
        expect($invoice->legacy_document_filename)->toBe('invoice-original.pdf');

        getJson('/api/v1/invoices/'.$invoice->id.'/download', $this->headers)
            ->assertStatus(200);

        unlink($tempPdf);
    });
});

describe('Legacy invoice integration guards', function () {

    it('blocks e-invoice submission for legacy invoices', function () {
        $invoice = Invoice::create([
            'invoice_number' => 'LEGACY-EINV-001',
            'client' => $this->client->company_name,
            'date' => '2025-01-01',
            'status' => 'paid',
            'subtotal' => 1000,
            'sst' => 80,
            'retention' => 0,
            'total' => 1080,
            'source' => 'legacy',
            'buyer_tin' => 'C12345678901',
            'buyer_type' => 'company',
        ]);

        postJson('/api/v1/invoices/'.$invoice->id.'/submit-einvoice', [], $this->headers)
            ->assertStatus(422)
            ->assertJsonPath('error', 'Legacy imported invoices cannot be submitted for e-invoicing');
    });

    it('generateForProject uses invoice_amount with no deduction at 0%', function () {
        postJson('/api/v1/projects/'.$this->project->id.'/generate-invoice', ['invoice_amount' => 10000, 'maincon_pct' => 0], $this->headers)
            ->assertStatus(201);

        $inv = Invoice::where('project_id', $this->project->id)->latest('id')->first();
        expect((float) $inv->total)->toBe(10000.0);
        expect(count($inv->items))->toBe(1);
    });

    it('generateForProject applies maincon deduction as a single net item', function () {
        postJson('/api/v1/projects/'.$this->project->id.'/generate-invoice', ['invoice_amount' => 10000, 'maincon_pct' => 15], $this->headers)
            ->assertStatus(201);

        $inv = Invoice::where('project_id', $this->project->id)->latest('id')->first();
        expect((float) $inv->total)->toBe(8500.0);
        expect(count($inv->items))->toBe(1);
        expect((float) $inv->items[0]['total'])->toBe(8500.0);
        expect($inv->items[0]['description'])->toContain('Project Completion');
    });

    it('generateForProject rejects maincon_pct outside 0-100', function () {
        postJson('/api/v1/projects/'.$this->project->id.'/generate-invoice', ['invoice_amount' => 1000, 'maincon_pct' => 150], $this->headers)
            ->assertStatus(422);
    });

    it('allows generateForProject when project only has legacy invoices', function () {
        Invoice::create([
            'invoice_number' => 'LEGACY-GATE-001',
            'project_id' => $this->project->id,
            'client' => $this->client->company_name,
            'date' => '2025-01-01',
            'status' => 'paid',
            'subtotal' => 1000,
            'sst' => 0,
            'retention' => 0,
            'total' => 1000,
            'source' => 'legacy',
        ]);

        postJson('/api/v1/projects/'.$this->project->id.'/generate-invoice', ['margin_pct' => 0], $this->headers)
            ->assertStatus(201)
            ->assertJsonPath('status', 'draft');

        $generated = Invoice::where('project_id', $this->project->id)
            ->where('invoice_number', '!=', 'LEGACY-GATE-001')
            ->first();
        expect($generated)->not->toBeNull();
        expect($generated->source)->toBe('system');
    });
});

describe('app:import-legacy-invoices command', function () {

    it('imports rows from CSV', function () {
        $csvPath = tempnam(sys_get_temp_dir(), 'legacy-import').'.csv';
        file_put_contents($csvPath, implode("\n", [
            'invoice_number,project_code,project_name,client,amount,status,date,due_date,paid_date,document',
            'CSV-INV-001,TEST-PRJ-001,,'.$this->client->company_name.',5000,paid,2025-03-01,2025-03-31,2025-04-01,',
            'CSV-INV-002,,Test Project,'.$this->client->company_name.',7500,unpaid,2025-04-01,2025-05-01,,',
        ]));

        artisan('app:import-legacy-invoices', ['--file' => $csvPath, '--force' => true])
            ->assertSuccessful();

        expect(Invoice::where('invoice_number', 'CSV-INV-001')->exists())->toBeTrue();
        expect(Invoice::where('invoice_number', 'CSV-INV-002')->exists())->toBeTrue();

        $inv1 = Invoice::where('invoice_number', 'CSV-INV-001')->first();
        expect($inv1->project_id)->toBe($this->project->id);
        expect($inv1->legacy_paid_date)->not->toBeNull();

        unlink($csvPath);
    });

    it('dry-run makes no changes', function () {
        $csvPath = tempnam(sys_get_temp_dir(), 'legacy-dryrun').'.csv';
        file_put_contents($csvPath, implode("\n", [
            'invoice_number,project_code,project_name,client,amount,status,date,due_date,paid_date,document',
            'DRY-INV-001,IMP-PRJ-001,,'.$this->client->company_name.',1000,unpaid,2025-01-01,,,',
        ]));

        artisan('app:import-legacy-invoices', ['--file' => $csvPath, '--dry-run' => true])
            ->assertSuccessful();

        expect(Invoice::where('invoice_number', 'DRY-INV-001')->exists())->toBeFalse();

        unlink($csvPath);
    });

    it('skips rows with no matching project when missing-project=skip', function () {
        $csvPath = tempnam(sys_get_temp_dir(), 'legacy-missing').'.csv';
        file_put_contents($csvPath, implode("\n", [
            'invoice_number,project_code,project_name,client,amount,status,date,due_date,paid_date,document',
            'MISS-INV-001,NO-SUCH-CODE,,'.$this->client->company_name.',1000,unpaid,2025-01-01,,,',
        ]));

        artisan('app:import-legacy-invoices', ['--file' => $csvPath, '--force' => true, '--missing-project' => 'skip'])
            ->assertSuccessful();

        expect(Invoice::where('invoice_number', 'MISS-INV-001')->exists())->toBeFalse();

        unlink($csvPath);
    });
});
