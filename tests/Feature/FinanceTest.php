<?php

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\SelfBilledInvoice;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

describe('Quotations', function () {

    it('returns 401 without token', function () {
        getJson('/api/v1/quotations')->assertStatus(401);
    });

    it('lists quotations', function () {
        getJson('/api/v1/quotations', $this->headers)
            ->assertStatus(200);
    });

    it('shows single quotation', function () {
        $q = Quotation::first();
        if (! $q) {
            $this->markTestSkipped('No quotations in database');
        }

        getJson('/api/v1/quotations/'.$q->id, $this->headers)
            ->assertStatus(200);
    });

    it('creates quotation with validation error', function () {
        postJson('/api/v1/quotations', [], $this->headers)
            ->assertStatus(422);
    });

});

describe('Contracts', function () {

    it('lists contracts', function () {
        getJson('/api/v1/contracts', $this->headers)
            ->assertStatus(200);
    });

    it('shows single contract', function () {
        $c = Contract::first();
        if (! $c) {
            $this->markTestSkipped('No contracts in database');
        }

        getJson('/api/v1/contracts/'.$c->id, $this->headers)
            ->assertStatus(200);
    });

});

describe('Invoices', function () {

    it('lists invoices', function () {
        getJson('/api/v1/invoices', $this->headers)
            ->assertStatus(200);
    });

    it('shows single invoice', function () {
        $i = Invoice::first();
        if (! $i) {
            $this->markTestSkipped('No invoices in database');
        }

        getJson('/api/v1/invoices/'.$i->id, $this->headers)
            ->assertStatus(200);
    });

    it('returns invoice payments', function () {
        $i = Invoice::first();
        if (! $i) {
            $this->markTestSkipped('No invoices in database');
        }

        getJson('/api/v1/invoices/'.$i->id.'/payments', $this->headers)
            ->assertStatus(200);
    });

});

describe('Self-Billed Invoices', function () {

    it('lists self-billed invoices', function () {
        getJson('/api/v1/self-billed-invoices', $this->headers)
            ->assertStatus(200);
    });

    it('shows single self-billed invoice', function () {
        $invoice = SelfBilledInvoice::first();
        if (! $invoice) {
            $this->markTestSkipped('No self-billed invoices in database');
        }

        getJson('/api/v1/self-billed-invoices/'.$invoice->id, $this->headers)
            ->assertStatus(200);
    });

});

describe('Payments & Services', function () {

    it('lists payments', function () {
        getJson('/api/v1/payments/pending', $this->headers)
            ->assertStatus(200);
    });

    it('lists service items', function () {
        getJson('/api/v1/service-items', $this->headers)
            ->assertStatus(200);
    });

});
