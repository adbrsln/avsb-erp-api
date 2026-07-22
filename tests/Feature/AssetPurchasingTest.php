<?php

use App\Models\Asset;
use App\Models\User;

use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

describe('Assets', function () {

    it('returns 401 without token', function () {
        getJson('/api/v1/assets')->assertStatus(401);
    });

    it('lists assets', function () {
        getJson('/api/v1/assets', $this->headers)
            ->assertStatus(200);
    });

    it('shows single asset', function () {
        $asset = Asset::first();
        if (! $asset) {
            $this->markTestSkipped('No assets in database');
        }

        getJson('/api/v1/assets/'.$asset->id, $this->headers)
            ->assertStatus(200);
    });

    it('returns asset types', function () {
        getJson('/api/v1/assets/types', $this->headers)
            ->assertStatus(200);
    });

    it('returns asset licenses', function () {
        $asset = Asset::first();
        if (! $asset) {
            $this->markTestSkipped('No assets in database');
        }

        getJson('/api/v1/assets/'.$asset->id.'/licenses', $this->headers)
            ->assertStatus(200);
    });

    it('returns asset movements', function () {
        $asset = Asset::first();
        if (! $asset) {
            $this->markTestSkipped('No assets in database');
        }

        getJson('/api/v1/assets/'.$asset->id.'/movements', $this->headers)
            ->assertStatus(200);
    });

    it('returns asset services', function () {
        $asset = Asset::first();
        if (! $asset) {
            $this->markTestSkipped('No assets in database');
        }

        getJson('/api/v1/assets/'.$asset->id.'/services', $this->headers)
            ->assertStatus(200);
    });

});

describe('Purchasing', function () {

    it('lists purchase orders', function () {
        getJson('/api/v1/purchase-orders', $this->headers)
            ->assertStatus(200);
    });

    it('lists bills', function () {
        getJson('/api/v1/bills', $this->headers)
            ->assertStatus(200);
    });

});

describe('Inventory', function () {

    it('lists inventory items', function () {
        getJson('/api/v1/inventory', $this->headers)
            ->assertStatus(200);
    });

    it('shows single inventory item', function () {
        $response = getJson('/api/v1/inventory', $this->headers);
        $items = $response->json('data') ?? $response->json();
        $id = is_array($items) && ! empty($items) ? $items[0]['id'] : 1;

        getJson('/api/v1/inventory/'.$id, $this->headers)
            ->assertStatus(200);
    });

    it('returns inventory transactions', function () {
        $response = getJson('/api/v1/inventory', $this->headers);
        $items = $response->json('data') ?? $response->json();
        $id = is_array($items) && ! empty($items) ? $items[0]['id'] : 1;

        getJson('/api/v1/inventory/'.$id.'/transactions', $this->headers)
            ->assertStatus(200);
    });

});
