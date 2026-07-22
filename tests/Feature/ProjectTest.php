<?php

use App\Models\Task;
use App\Models\User;

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
