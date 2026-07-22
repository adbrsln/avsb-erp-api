<?php

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
        $response = getJson('/api/v1/quotations', $this->headers);
        $items = $response->json('data') ?? $response->json();
        $id = is_array($items) && ! empty($items) ? $items[0]['id'] : 1;

        getJson('/api/v1/quotations/'.$id, $this->headers)
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
        $response = getJson('/api/v1/contracts', $this->headers);
        $items = $response->json('data') ?? $response->json();
        $id = is_array($items) && ! empty($items) ? $items[0]['id'] : 1;

        getJson('/api/v1/contracts/'.$id, $this->headers)
            ->assertStatus(200);
    });

});

describe('Invoices', function () {

    it('lists invoices', function () {
        getJson('/api/v1/invoices', $this->headers)
            ->assertStatus(200);
    });

    it('shows single invoice', function () {
        $response = getJson('/api/v1/invoices', $this->headers);
        $items = $response->json('data') ?? $response->json();
        $id = is_array($items) && ! empty($items) ? $items[0]['id'] : 1;

        getJson('/api/v1/invoices/'.$id, $this->headers)
            ->assertStatus(200);
    });

    it('returns invoice payments', function () {
        $response = getJson('/api/v1/invoices', $this->headers);
        $items = $response->json('data') ?? $response->json();
        $id = is_array($items) && ! empty($items) ? $items[0]['id'] : 1;

        getJson('/api/v1/invoices/'.$id.'/payments', $this->headers)
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
        getJson('/api/v1/payments', $this->headers)
            ->assertStatus(200);
    });

    it('lists service items', function () {
        getJson('/api/v1/service-items', $this->headers)
            ->assertStatus(200);
    });

});
