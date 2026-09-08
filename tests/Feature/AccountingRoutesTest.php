<?php

use App\Models\ChartOfAccount;
use App\Models\User;

use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->super = User::where('email', 'superadmin@azamventures.com')->first();
    $this->headers = ['Authorization' => 'Bearer '.$this->super->createToken('test')->plainTextToken];
});

it('serves journal entries on the frontend path', function () {
    getJson('/api/v1/journal-entries?page=1', $this->headers)
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('serves the accounting reports on the frontend /reports paths', function () {
    foreach (['trial-balance', 'profit-loss', 'balance-sheet', 'ar-aging', 'ap-aging'] as $report) {
        getJson('/api/v1/reports/'.$report, $this->headers)->assertOk();
    }

    // General ledger requires an account_id.
    $account = ChartOfAccount::first();
    getJson('/api/v1/reports/general-ledger?account_id='.$account->id, $this->headers)->assertOk();
});
