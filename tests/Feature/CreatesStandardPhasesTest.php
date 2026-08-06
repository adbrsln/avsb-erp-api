<?php

namespace Database\Seeders\Concerns;

use App\Models\Client;
use App\Models\Phase;
use App\Models\Project;
use RuntimeException;

class CreatesStandardPhasesHarness
{
    use CreatesStandardPhases;

    public function create(Project $project, array|PhaseDefinition|null $phases = null, string $defaultStatus = 'pending'): void
    {
        $this->createStandardPhases($project, $phases, $defaultStatus);
    }
}

class TestPhaseDefinition implements PhaseDefinition
{
    public function __construct(
        private string $name,
        private ?string $start = null,
        private ?string $end = null,
        private string $status = 'pending',
        private ?string $remarks = null,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function startDate(): ?string
    {
        return $this->start;
    }

    public function endDate(): ?string
    {
        return $this->end;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function remarks(): ?string
    {
        return $this->remarks;
    }
}

function standardPhaseProject(string $code): Project
{
    $client = Client::firstOrCreate(['client_code' => 'TNB'], ['company_name' => 'Tenaga Nasional Berhad']);

    return Project::create([
        'name' => 'Phase trait job '.$code,
        'project_code' => $code,
        'client' => 'Tenaga Nasional Berhad',
        'client_id' => $client->id,
        'status' => 'active',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ]);
}

it('creates the 11 standard phases with no custom list (default pending)', function () {
    $project = standardPhaseProject('AV-TRAIT-001');

    (new CreatesStandardPhasesHarness)->create($project);

    expect($project->phases()->count())->toBe(11);
    expect($project->phases()->pluck('status')->unique()->all())->toBe(['pending']);
    expect($project->phases()->orderBy('order')->pluck('name')->first())->toBe('PO Confirmation');
    expect($project->phases()->orderBy('order')->pluck('name')->last())->toBe('Payment Settlement (30 days)');
});

it('creates custom phases from names only, spread evenly', function () {
    $project = standardPhaseProject('AV-TRAIT-002');

    (new CreatesStandardPhasesHarness)->create($project, [['name' => 'Alpha'], ['name' => 'Beta'], ['name' => 'Gamma']]);

    $phases = $project->phases()->orderBy('order')->get();
    expect($phases->count())->toBe(3);
    expect($phases->pluck('name')->all())->toBe(['Alpha', 'Beta', 'Gamma']);
    expect($phases[0]->start_date->format('Y-m-d'))->toBe('2026-01-01');
    expect($phases[2]->start_date->format('Y-m-d'))->toBe('2026-01-31'); // last phase starts on project end
});

it('uses explicit start_date/end_date when provided', function () {
    $project = standardPhaseProject('AV-TRAIT-003');

    (new CreatesStandardPhasesHarness)->create($project, [
        ['name' => 'LKS', 'start_date' => '2026-01-05', 'end_date' => '2026-01-12'],
        ['name' => 'TNB', 'start_date' => '2026-01-15', 'end_date' => '2026-01-20'],
    ]);

    $lks = $project->phases()->where('name', 'LKS')->first();
    expect($lks->start_date->format('Y-m-d'))->toBe('2026-01-05');
    expect($lks->end_date->format('Y-m-d'))->toBe('2026-01-12');
    expect($project->phases()->where('name', 'TNB')->first()->start_date->format('Y-m-d'))->toBe('2026-01-15');
});

it('applies default status when per-phase status missing', function () {
    $project = standardPhaseProject('AV-TRAIT-004');

    (new CreatesStandardPhasesHarness)->create($project, [['name' => 'Alpha']], 'completed');

    $phase = $project->phases()->first();
    expect($phase->status)->toBe('completed');
    expect($phase->completed_at)->not->toBeNull();
});

it('per-phase status overrides the default status', function () {
    $project = standardPhaseProject('AV-TRAIT-005');

    (new CreatesStandardPhasesHarness)->create($project, [
        ['name' => 'Done', 'status' => 'completed'],
        ['name' => 'Wait', 'status' => 'pending'],
    ], 'completed');

    expect($project->phases()->where('name', 'Done')->first()->status)->toBe('completed');
    expect($project->phases()->where('name', 'Wait')->first()->status)->toBe('pending');
});

it('stores remarks on the phase description', function () {
    $project = standardPhaseProject('AV-TRAIT-006');

    (new CreatesStandardPhasesHarness)->create($project, [
        ['name' => 'Alpha', 'remarks' => 'Site access secured'],
    ]);

    expect($project->phases()->first()->description)->toBe('Site access secured');
});

it('accepts PhaseDefinition interface objects', function () {
    $project = standardPhaseProject('AV-TRAIT-007');

    (new CreatesStandardPhasesHarness)->create($project, [
        new TestPhaseDefinition('Alpha', '2026-01-02', '2026-01-04', 'completed', 'Via interface'),
    ]);

    $phase = $project->phases()->first();
    expect($phase->name)->toBe('Alpha');
    expect($phase->status)->toBe('completed');
    expect($phase->start_date->format('Y-m-d'))->toBe('2026-01-02');
    expect($phase->description)->toBe('Via interface');
});

it('rejects an invalid per-phase status', function () {
    $project = standardPhaseProject('AV-TRAIT-008');

    expect(fn () => (new CreatesStandardPhasesHarness)->create($project, [
        ['name' => 'Alpha', 'status' => 'banana'],
    ]))->toThrow(RuntimeException::class, 'status');
});

it('rejects an invalid default status', function () {
    $project = standardPhaseProject('AV-TRAIT-009');

    expect(fn () => (new CreatesStandardPhasesHarness)->create($project, [['name' => 'Alpha']], 'banana'))
        ->toThrow(RuntimeException::class, 'status');
});

it('rejects a malformed date', function () {
    $project = standardPhaseProject('AV-TRAIT-010');

    expect(fn () => (new CreatesStandardPhasesHarness)->create($project, [
        ['name' => 'Alpha', 'start_date' => 'not-a-date'],
    ]))->toThrow(RuntimeException::class, 'start_date');
});

it('rejects end date before start date', function () {
    $project = standardPhaseProject('AV-TRAIT-011');

    expect(fn () => (new CreatesStandardPhasesHarness)->create($project, [
        ['name' => 'Alpha', 'start_date' => '2026-01-10', 'end_date' => '2026-01-05'],
    ]))->toThrow(RuntimeException::class, 'end_date');
});

it('rejects unknown keys in a phase definition', function () {
    $project = standardPhaseProject('AV-TRAIT-012');

    expect(fn () => (new CreatesStandardPhasesHarness)->create($project, [
        ['name' => 'Alpha', 'staus' => 'completed'], // typo
    ]))->toThrow(RuntimeException::class, 'staus');
});

it('rejects an empty phase name', function () {
    $project = standardPhaseProject('AV-TRAIT-013');

    expect(fn () => (new CreatesStandardPhasesHarness)->create($project, [
        ['name' => ''],
    ]))->toThrow(RuntimeException::class, 'name');
});
