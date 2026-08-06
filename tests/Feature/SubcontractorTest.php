<?php

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Project;
use App\Models\ProjectSubcontractor;
use App\Models\Subcontractor;
use App\Models\SubcontractorClaim;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

describe('Project subcontractor claims', function () {

    it('creates a progress claim on a project subcontractor', function () {
        $client = Client::firstOrCreate(['client_code' => 'TNB'], ['company_name' => 'Tenaga Nasional Berhad']);
        $project = Project::create([
            'name' => 'Claim fixture job',
            'project_code' => 'AV-CLAIM-001',
            'client' => 'Tenaga Nasional Berhad',
            'client_id' => $client->id,
            'status' => 'active',
        ]);
        $sub = Subcontractor::firstOrCreate(
            ['subcontractor_code' => 'EB'],
            ['company_name' => 'Elektron Berkat', 'status' => 'active']
        );
        $assignment = ProjectSubcontractor::create([
            'project_id' => $project->id,
            'subcontractor_id' => $sub->id,
            'status' => 'active',
        ]);

        postJson('/api/v1/project-subcontractors/'.$assignment->id.'/claims', [
            'claimed_amount' => 10000,
            'claim_date' => '2026-01-10',
            'retention_pct' => 5,
            'work_done_pct' => 50,
        ], $this->headers)
            ->assertStatus(201)
            ->assertJsonPath('status', 'draft');

        $claim = SubcontractorClaim::where('project_subcontractor_id', $assignment->id)->first();
        expect($claim)->not->toBeNull();
        expect($claim->claim_number)->not->toBeNull();
        expect((float) $claim->claimed_amount)->toBe(10000.0);
        expect((float) $claim->retention_deducted)->toBe(500.0);
        expect((float) $claim->net_payable)->toBe(9500.0);
    });
});

describe('Subcontractors', function () {

    it('returns 401 without token', function () {
        getJson('/api/v1/subcontractors')->assertStatus(401);
    });

    it('lists all subcontractors', function () {
        getJson('/api/v1/subcontractors', $this->headers)
            ->assertStatus(200);
    });

    it('shows single subcontractor', function () {
        $sub = Subcontractor::first();
        if (! $sub) {
            $this->markTestSkipped('No subcontractors in database');
        }

        getJson('/api/v1/subcontractors/'.$sub->id, $this->headers)
            ->assertStatus(200);
    });

    it('creates subcontractor with validation error', function () {
        postJson('/api/v1/subcontractors', [], $this->headers)
            ->assertStatus(422);
    });

    it('returns subcontractor projects', function () {
        $sub = Subcontractor::first();
        if (! $sub) {
            $this->markTestSkipped('No subcontractors in database');
        }

        getJson('/api/v1/subcontractors/'.$sub->id.'/projects', $this->headers)
            ->assertStatus(200);
    });

    it('returns subcontractor claims', function () {
        $sub = Subcontractor::first();
        if (! $sub) {
            $this->markTestSkipped('No subcontractors in database');
        }

        getJson('/api/v1/subcontractors/'.$sub->id.'/claims', $this->headers)
            ->assertStatus(200);
    });

});

describe('Subcontractor Claims', function () {

    beforeEach(function () {
        ChartOfAccount::firstOrCreate(['code' => '1102'], ['code' => '1102', 'name' => 'Bank', 'type' => 'asset']);
        ChartOfAccount::firstOrCreate(['code' => '5103'], ['code' => '5103', 'name' => 'Subcontracting Costs', 'type' => 'expense']);
        ChartOfAccount::firstOrCreate(['code' => '2109'], ['code' => '2109', 'name' => 'Retention Payable', 'type' => 'liability']);
    });

    function makeClaimFixture(): array
    {
        $client = Client::firstOrCreate(['client_code' => 'TNB'], ['company_name' => 'Tenaga Nasional Berhad']);
        $project = Project::create([
            'name' => 'Claim fixture job',
            'project_code' => 'AV-CLAIM-'.rand(1000, 9999),
            'client' => 'Tenaga Nasional Berhad',
            'client_id' => $client->id,
            'status' => 'active',
        ]);
        $sub = Subcontractor::firstOrCreate(
            ['subcontractor_code' => 'EB'],
            ['company_name' => 'Elektron Berkat', 'status' => 'active']
        );
        $assignment = ProjectSubcontractor::create([
            'project_id' => $project->id,
            'subcontractor_id' => $sub->id,
            'status' => 'active',
        ]);

        return [$project, $sub, $assignment];
    }

    function approveClaimFixture(array $headers): int
    {
        [, , $assignment] = makeClaimFixture();
        $res = postJson('/api/v1/project-subcontractors/'.$assignment->id.'/claims', [
            'claimed_amount' => 10000,
            'claim_date' => '2026-01-10',
            'retention_pct' => 5,
            'work_done_pct' => 50,
        ], $headers);
        $claimId = $res->json('id');
        postJson('/api/v1/subcontractor-claims/'.$claimId.'/submit', [], $headers)->assertStatus(200);
        postJson('/api/v1/subcontractor-claims/'.$claimId.'/verify', [], $headers)->assertStatus(200);
        postJson('/api/v1/subcontractor-claims/'.$claimId.'/approve', [], $headers)->assertStatus(200);

        return $claimId;
    }

    it('creates an AP bill with receive JE when a claim is approved', function () {
        $claimId = approveClaimFixture($this->headers);
        $claim = SubcontractorClaim::find($claimId);

        $bill = Bill::where('bill_number', $claim->claim_number)->first();
        expect($bill)->not->toBeNull();
        expect($bill->subcontractor_id)->not->toBeNull();
        expect((float) $bill->total)->toBe(10000.0);
        expect($bill->status)->toBe('unpaid');

        $je = JournalEntry::where('reference_type', 'bill')->where('reference_id', $bill->id)->first();
        expect($je)->not->toBeNull();
        expect((float) JournalEntryLine::where('journal_entry_id', $je->id)->where('debit', '>', 0)->sum('debit'))->toBe(10000.0);
        expect((float) JournalEntryLine::where('journal_entry_id', $je->id)->where('credit', '>', 0)->sum('credit'))->toBe(10000.0);
    });

    it('pays the claim against its AP bill and holds retention', function () {
        $claimId = approveClaimFixture($this->headers);
        $claim = SubcontractorClaim::find($claimId);

        postJson('/api/v1/subcontractor-claims/'.$claimId.'/mark-paid', ['payment_reference' => 'REF-X'], $this->headers)
            ->assertStatus(200);

        $bill = Bill::where('bill_number', $claim->claim_number)->first();
        $payment = BillPayment::where('bill_id', $bill->id)->first();
        expect($payment)->not->toBeNull();
        expect((float) $payment->amount)->toBe(9500.0);
        expect($bill->refresh()->balance)->toBe(500.0);
        expect($bill->status)->toBe('partially_paid');

        $paymentJe = JournalEntry::where('reference_type', 'bill_payment')->where('reference_id', $bill->id)->first();
        expect($paymentJe)->not->toBeNull();
        expect((float) JournalEntryLine::where('journal_entry_id', $paymentJe->id)->where('credit', '>', 0)->sum('credit'))->toBe(9500.0);
    });

    it('lists all subcontractor claims', function () {
        getJson('/api/v1/subcontractor-claims', $this->headers)
            ->assertStatus(200);
    });

    it('shows single subcontractor claim', function () {
        $claim = SubcontractorClaim::first();
        if (! $claim) {
            $this->markTestSkipped('No subcontractor claims in database');
        }

        getJson('/api/v1/subcontractor-claims/'.$claim->id, $this->headers)
            ->assertStatus(200);
    });

});

describe('Subcontractor PICs', function () {

    it('lists subcontractor PICs', function () {
        $sub = Subcontractor::first();
        if (! $sub) {
            $this->markTestSkipped('No subcontractors in database');
        }

        getJson('/api/v1/subcontractors/'.$sub->id.'/pics', $this->headers)
            ->assertStatus(200);
    });

});
