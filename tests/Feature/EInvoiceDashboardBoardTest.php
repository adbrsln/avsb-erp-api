<?php

use App\Models\User;

use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

describe('Dashboard', function () {

    it('returns 401 without token', function () {
        getJson('/api/v1/dashboard/summary')->assertStatus(401);
    });

    it('returns dashboard summary', function () {
        getJson('/api/v1/dashboard/summary', $this->headers)
            ->assertStatus(200);
    });

});

describe('Board', function () {

    it('returns board summary', function () {
        getJson('/api/v1/board/summary', $this->headers)
            ->assertStatus(200);
    });

    it('lists board projects', function () {
        getJson('/api/v1/board/projects', $this->headers)
            ->assertStatus(200);
    });

});

describe('E-Invoice', function () {

    it('returns e-invoice settings', function () {
        getJson('/api/v1/einvoice/settings', $this->headers)
            ->assertStatus(200);
    });

    it('returns tax codes', function () {
        getJson('/api/v1/einvoice/tax-codes', $this->headers)
            ->assertStatus(200);
    });

});

describe('Statutory', function () {

    it('returns EPF schedules', function () {
        getJson('/api/v1/epf/schedules', $this->headers)
            ->assertStatus(200);
    });

    it('returns SOCSO tiers', function () {
        getJson('/api/v1/socso/tiers', $this->headers)
            ->assertStatus(200);
    });

    it('returns EIS tiers', function () {
        getJson('/api/v1/eis/tiers', $this->headers)
            ->assertStatus(200);
    });

});

describe('Misc', function () {

    it('returns service types', function () {
        getJson('/api/v1/service-types', $this->headers)
            ->assertStatus(200);
    });

    it('returns approvals', function () {
        getJson('/api/v1/approvals', $this->headers)
            ->assertStatus(200);
    });

    it('returns notification preferences', function () {
        getJson('/api/v1/notification-preferences', $this->headers)
            ->assertStatus(200);
    });

    it('returns pay runs', function () {
        getJson('/api/v1/pay-runs', $this->headers)
            ->assertStatus(200);
    });

    it('returns system diagnostics', function () {
        getJson('/api/v1/system/diagnostics', $this->headers)
            ->assertStatus(200);
    });

});
