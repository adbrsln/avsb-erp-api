<?php

use App\Models\User;
use App\Models\Vendor;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

describe('Clients', function () {

    it('returns 401 without token', function () {
        getJson('/api/v1/clients')->assertStatus(401);
    });

    it('lists all clients', function () {
        getJson('/api/v1/clients', $this->headers)
            ->assertStatus(200);
    });

    it('shows single client', function () {
        $response = getJson('/api/v1/clients', $this->headers);
        $clients = $response->json('data') ?? $response->json();
        $clientId = is_array($clients) && ! empty($clients) ? $clients[0]['id'] : 1;

        getJson('/api/v1/clients/'.$clientId, $this->headers)
            ->assertStatus(200);
    });

    it('returns client PICs', function () {
        $response = getJson('/api/v1/clients', $this->headers);
        $clients = $response->json('data') ?? $response->json();
        $clientId = is_array($clients) && ! empty($clients) ? $clients[0]['id'] : 1;

        getJson('/api/v1/clients/'.$clientId.'/pics', $this->headers)
            ->assertStatus(200);
    });

    it('creates client with validation error', function () {
        postJson('/api/v1/clients', [], $this->headers)
            ->assertStatus(422);
    });

    it('creates client successfully', function () {
        $name = 'Test Client '.time();
        postJson('/api/v1/clients', [
            'company_name' => $name,
            'registration_no' => 'REG-'.time(),
            'tax_id' => 'TAX-'.time(),
            'email' => 'client_'.time().'@example.com',
            'phone' => '0123456789',
            'address' => 'Test Address',
            'buyer_type' => 'company',
        ], $this->headers)
            ->assertStatus(201);
    });

    it('updates client', function () {
        $response = getJson('/api/v1/clients', $this->headers);
        $clients = $response->json('data') ?? $response->json();
        if (! is_array($clients) || empty($clients)) {
            $this->markTestSkipped('No clients in database');
        }
        $clientId = $clients[0]['id'];

        putJson('/api/v1/clients/'.$clientId, [
            'company_name' => 'Updated Client '.time(),
            'buyer_type' => 'company',
            'registration_no' => 'REG-UPD-'.time(),
            'tax_id' => 'TAX-UPD-'.time(),
        ], $this->headers)
            ->assertStatus(200);
    });

});

describe('Vendors', function () {

    it('lists all vendors', function () {
        getJson('/api/v1/vendors', $this->headers)
            ->assertStatus(200);
    });

    it('shows single vendor', function () {
        $vendor = Vendor::first();
        if (! $vendor) {
            $this->markTestSkipped('No vendors in database');
        }

        getJson('/api/v1/vendors/'.$vendor->id, $this->headers)
            ->assertStatus(200);
    });

});
