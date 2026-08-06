<?php

use App\Models\Client;
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
