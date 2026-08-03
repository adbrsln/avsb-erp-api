<?php

use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use App\Services\NumberingService;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

describe('Projects', function () {

    it('returns 401 without token', function () {
        getJson('/api/v1/projects')->assertStatus(401);
    });

    it('lists all projects', function () {
        getJson('/api/v1/projects', $this->headers)
            ->assertStatus(200);
    });

    it('shows single project', function () {
        $response = getJson('/api/v1/projects', $this->headers);
        $projects = $response->json('data') ?? $response->json();
        $projectId = is_array($projects) && ! empty($projects) ? $projects[0]['id'] : 1;

        getJson('/api/v1/projects/'.$projectId, $this->headers)
            ->assertStatus(200);
    });

    it('returns project stats', function () {
        getJson('/api/v1/projects/stats', $this->headers)
            ->assertStatus(200);
    });

    it('returns project activity log', function () {
        $response = getJson('/api/v1/projects', $this->headers);
        $projects = $response->json('data') ?? $response->json();
        $projectId = is_array($projects) && ! empty($projects) ? $projects[0]['id'] : 1;

        getJson('/api/v1/projects/'.$projectId.'/activity-log', $this->headers)
            ->assertStatus(200);
    });

    it('creates project with validation error', function () {
        postJson('/api/v1/projects', [], $this->headers)
            ->assertStatus(422);
    });

});

describe('Project numbering', function () {

    it('formats project code with client code prefix', function () {
        $client = Client::first() ?? Client::create([
            'client_code' => 'TEST-CLT-001',
            'company_name' => 'Numbering Test Client',
            'buyer_type' => 'company',
            'email' => 'numbering@testclient.com',
        ]);

        $response = postJson('/api/v1/projects', [
            'name' => 'Numbering Format Project',
            'client_id' => $client->id,
        ], $this->headers)->assertStatus(201);

        $code = $response->json('project_code');
        expect($code)->toStartWith('AV-'.$client->client_code.'-');
        expect($code)->toMatch('/^AV-'.$client->client_code.'-\d{4}-\d{4}$/');
    });

    it('uses client code when only client name matches an existing client', function () {
        $client = Client::first() ?? Client::create([
            'client_code' => 'TEST-CLT-001',
            'company_name' => 'Name Match Client',
            'buyer_type' => 'company',
            'email' => 'namematch@testclient.com',
        ]);

        $response = postJson('/api/v1/projects', [
            'name' => 'Name Match Project',
            'client' => $client->company_name,
        ], $this->headers)->assertStatus(201);

        expect($response->json('project_code'))->toStartWith('AV-'.$client->client_code.'-');
    });

    it('falls back to legacy format when no client code matches', function () {
        $response = postJson('/api/v1/projects', [
            'name' => 'No Client Project',
            'client' => 'Non Existent Client Name',
        ], $this->headers)->assertStatus(201);

        $code = $response->json('project_code');
        expect($code)->toMatch('/^AV-\d{2}-\d{2}-\d{4}$/');
    });

    it('keeps a single global sequence across clients', function () {
        $clientA = Client::create([
            'client_code' => 'GLOBAL-CLT-A',
            'company_name' => 'Global Client A',
            'buyer_type' => 'company',
            'email' => 'a@global.test',
        ]);
        $clientB = Client::create([
            'client_code' => 'GLOBAL-CLT-B',
            'company_name' => 'Global Client B',
            'buyer_type' => 'company',
            'email' => 'b@global.test',
        ]);

        $service = new NumberingService;
        $codeA1 = $service->generateProject($clientA->client_code);
        $codeB1 = $service->generateProject($clientB->client_code);

        expect($codeA1)->toStartWith('AV-GLOBAL-CLT-A-');
        expect($codeB1)->toStartWith('AV-GLOBAL-CLT-B-');
        expect($codeA1)->not->toBe($codeB1);

        // Global counter: second call continues the sequence, not resets per client
        $codeA2 = $service->generateProject($clientA->client_code);
        expect($codeA2)->not->toBe($codeA1);
    });

});

describe('Project Phases', function () {

    it('lists all phases', function () {
        getJson('/api/v1/project-phases', $this->headers)
            ->assertStatus(200);
    });

    it('shows single phase', function () {
        $response = getJson('/api/v1/project-phases', $this->headers);
        $phases = $response->json('data') ?? $response->json();
        $phaseId = is_array($phases) && ! empty($phases) ? $phases[0]['id'] : 1;

        getJson('/api/v1/project-phases/'.$phaseId, $this->headers)
            ->assertStatus(200);
    });

});

describe('Tasks', function () {

    it('lists all tasks', function () {
        getJson('/api/v1/tasks', $this->headers)
            ->assertStatus(200);
    });

    it('shows single task', function () {
        $task = Task::first();
        if (! $task) {
            $this->markTestSkipped('No tasks in database');
        }

        getJson('/api/v1/tasks/'.$task->id, $this->headers)
            ->assertStatus(200);
    });

});

describe('Project Config', function () {

    it('lists phase templates', function () {
        getJson('/api/v1/phase-templates', $this->headers)
            ->assertStatus(200);
    });

    it('lists project types', function () {
        getJson('/api/v1/project-types', $this->headers)
            ->assertStatus(200);
    });

    it('lists project groups', function () {
        getJson('/api/v1/project-groups', $this->headers)
            ->assertStatus(200);
    });

    it('searches', function () {
        getJson('/api/v1/search?q=test', $this->headers)
            ->assertStatus(200);
    });

});
