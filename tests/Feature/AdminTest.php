<?php

use App\Models\ChartOfAccount;
use App\Models\EInvoiceCredential;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

describe('Accounting', function () {

    it('returns 401 without token', function () {
        getJson('/api/v1/chart-of-accounts')->assertStatus(401);
    });

    it('lists chart of accounts', function () {
        getJson('/api/v1/chart-of-accounts', $this->headers)
            ->assertStatus(200);
    });

    it('lists journal entries', function () {
        getJson('/api/v1/accounting/journal-entries', $this->headers)
            ->assertStatus(200);
    });

    it('returns trial balance', function () {
        getJson('/api/v1/accounting/trial-balance', $this->headers)
            ->assertStatus(200);
    });

    it('returns balance sheet', function () {
        getJson('/api/v1/accounting/balance-sheet', $this->headers)
            ->assertStatus(200);
    });

    it('returns general ledger', function () {
        $account = ChartOfAccount::first();
        if (! $account) {
            $this->markTestSkipped('No chart of accounts in database');
        }

        getJson('/api/v1/accounting/general-ledger?account_id='.$account->id, $this->headers)
            ->assertStatus(200);
    });

    it('returns profit and loss', function () {
        getJson('/api/v1/accounting/profit-loss', $this->headers)
            ->assertStatus(200);
    });

    it('returns AR aging', function () {
        getJson('/api/v1/accounting/ar-aging', $this->headers)
            ->assertStatus(200);
    });

    it('returns AP aging', function () {
        getJson('/api/v1/accounting/ap-aging', $this->headers)
            ->assertStatus(200);
    });

});

describe('Fiscal Periods', function () {

    it('lists fiscal periods', function () {
        getJson('/api/v1/fiscal-periods', $this->headers)
            ->assertStatus(200);
    });

});

describe('Notifications', function () {

    it('lists notifications', function () {
        getJson('/api/v1/notifications', $this->headers)
            ->assertStatus(200);
    });

});

describe('Activity Logs', function () {

    it('lists activity logs', function () {
        getJson('/api/v1/activity-logs', $this->headers)
            ->assertStatus(200);
    });

});

describe('Users', function () {

    it('lists users', function () {
        getJson('/api/v1/users', $this->headers)
            ->assertStatus(200);
    });

});

describe('Company Settings', function () {

    it('returns company settings', function () {
        getJson('/api/v1/company-settings', $this->headers)
            ->assertStatus(200);
    });

});

describe('E-Invoice', function () {

    it('tests e-invoice connection', function () {
        $credential = EInvoiceCredential::where('is_active', true)->first();
        if (! $credential) {
            $this->markTestSkipped('No active e-invoice credentials configured');
        }

        postJson('/api/v1/einvoice/test-connection', [], $this->headers)
            ->assertStatus(200);
    });

});

describe('System', function () {

    it('pings', function () {
        getJson('/api/v1/system/ping', $this->headers)
            ->assertStatus(200)
            ->assertJsonStructure(['pong', 'time']);
    });

    it('returns system health', function () {
        getJson('/api/v1/system/health', $this->headers)
            ->assertStatus(200);
    });

});
