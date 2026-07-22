<?php

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
