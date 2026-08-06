<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Phase;
use App\Models\Project;
use Illuminate\Support\Facades\Artisan;

function makeMaintenanceProject(string $code, string $po): Project
{
    $client = Client::firstOrCreate(['client_code' => 'TNB'], ['company_name' => 'Tenaga Nasional Berhad']);

    return Project::create([
        'name' => 'Maintenance job '.$code,
        'project_code' => $code,
        'client' => 'Tenaga Nasional Berhad',
        'client_id' => $client->id,
        'po_number' => $po,
        'location' => 'SHAH ALAM',
        'status' => 'active',
    ]);
}

function addMaintenancePhase(Project $project, string $name, string $status = 'in_progress'): Phase
{
    return Phase::create([
        'project_id' => $project->id,
        'name' => $name,
        'order' => $project->phases()->count() + 1,
        'status' => $status,
        'started_at' => $status === 'in_progress' ? '2026-01-01 08:00:00' : null,
        'started_by' => $status === 'in_progress' ? 1 : null,
        'completed_at' => $status === 'completed' ? '2026-01-05 17:00:00' : null,
    ]);
}

it('sets all phases to completed and stamps completed_at', function () {
    $project = makeMaintenanceProject('AV-MNT-001', 'PO-001');
    addMaintenancePhase($project, 'Site Visit', 'in_progress');
    addMaintenancePhase($project, 'LKS', 'pending');

    $exit = Artisan::call('app:phase-maintenance', [
        '--projects' => 'AV-MNT-001',
        '--status' => 'completed',
        '--force' => true,
    ]);

    expect($exit)->toBe(0);
    $project->phases()->get()->each(function (Phase $phase) {
        expect($phase->status)->toBe('completed');
        expect($phase->completed_at)->not->toBeNull();
    });
});

it('sets phases to pending and clears started/completed fields', function () {
    $project = makeMaintenanceProject('AV-MNT-002', 'PO-002');
    addMaintenancePhase($project, 'Site Visit', 'in_progress');
    addMaintenancePhase($project, 'LKS', 'completed');

    $exit = Artisan::call('app:phase-maintenance', [
        '--projects' => 'AV-MNT-002',
        '--status' => 'pending',
        '--force' => true,
    ]);

    expect($exit)->toBe(0);
    $project->phases()->get()->each(function (Phase $phase) {
        expect($phase->status)->toBe('pending');
        expect($phase->completed_at)->toBeNull();
        expect($phase->completed_by)->toBeNull();
        expect($phase->started_at)->toBeNull();
        expect($phase->started_by)->toBeNull();
    });
});

it('resolves a single project by po_number with --po', function () {
    $project = makeMaintenanceProject('AV-MNT-003', 'PO-003');
    addMaintenancePhase($project, 'Site Visit', 'in_progress');

    $exit = Artisan::call('app:phase-maintenance', [
        '--po' => 'PO-003',
        '--status' => 'completed',
        '--force' => true,
    ]);

    expect($exit)->toBe(0);
    expect($project->phases()->first()->status)->toBe('completed');
});

it('handles multiple project codes with --projects', function () {
    $a = makeMaintenanceProject('AV-MNT-004', 'PO-004');
    $b = makeMaintenanceProject('AV-MNT-005', 'PO-005');
    addMaintenancePhase($a, 'Site Visit', 'in_progress');
    addMaintenancePhase($b, 'Site Visit', 'in_progress');

    $exit = Artisan::call('app:phase-maintenance', [
        '--projects' => 'AV-MNT-004,AV-MNT-005',
        '--status' => 'completed',
        '--force' => true,
    ]);

    expect($exit)->toBe(0);
    expect(Phase::where('status', 'completed')->count())->toBe(2);
});

it('restricts to a specific phase name with --phase', function () {
    $project = makeMaintenanceProject('AV-MNT-006', 'PO-006');
    addMaintenancePhase($project, 'Site Visit', 'in_progress');
    addMaintenancePhase($project, 'LKS', 'in_progress');

    $exit = Artisan::call('app:phase-maintenance', [
        '--projects' => 'AV-MNT-006',
        '--status' => 'completed',
        '--phase' => 'LKS',
        '--force' => true,
    ]);

    expect($exit)->toBe(0);
    expect($project->phases()->where('name', 'LKS')->first()->status)->toBe('completed');
    expect($project->phases()->where('name', 'Site Visit')->first()->status)->toBe('in_progress');
});

it('does not touch projects or create invoices when all phases complete', function () {
    $project = makeMaintenanceProject('AV-MNT-007', 'PO-007');
    $project->update(['budget_amount' => 50000]);
    addMaintenancePhase($project, 'Site Visit', 'in_progress');

    $exit = Artisan::call('app:phase-maintenance', [
        '--projects' => 'AV-MNT-007',
        '--status' => 'completed',
        '--force' => true,
    ]);

    expect($exit)->toBe(0);
    expect($project->refresh()->status)->toBe('active'); // NOT auto-completed
    expect(Invoice::count())->toBe(0); // no auto invoice
});

it('is idempotent — second run reports 0 changed', function () {
    $project = makeMaintenanceProject('AV-MNT-008', 'PO-008');
    addMaintenancePhase($project, 'Site Visit', 'in_progress');

    Artisan::call('app:phase-maintenance', ['--projects' => 'AV-MNT-008', '--status' => 'completed', '--force' => true]);
    $exit = Artisan::call('app:phase-maintenance', ['--projects' => 'AV-MNT-008', '--status' => 'completed', '--force' => true]);

    expect($exit)->toBe(0);
    expect(Artisan::output())->toContain('0');
});

it('fails on unknown project code', function () {
    $exit = Artisan::call('app:phase-maintenance', [
        '--projects' => 'AV-NOT-REAL',
        '--status' => 'completed',
        '--force' => true,
    ]);

    expect($exit)->toBe(1);
});

it('treats --phase=All phases as all phases', function () {
    $project = makeMaintenanceProject('AV-MNT-010', 'PO-010');
    addMaintenancePhase($project, 'Site Visit', 'in_progress');
    addMaintenancePhase($project, 'LKS', 'in_progress');

    $exit = Artisan::call('app:phase-maintenance', [
        '--projects' => 'AV-MNT-010',
        '--status' => 'completed',
        '--phase' => 'All phases',
        '--force' => true,
    ]);

    expect($exit)->toBe(0);
    expect($project->phases()->where('status', 'completed')->count())->toBe(2);
});

it('fails on invalid --status value', function () {
    $project = makeMaintenanceProject('AV-MNT-009', 'PO-009');
    addMaintenancePhase($project, 'Site Visit', 'in_progress');

    $exit = Artisan::call('app:phase-maintenance', [
        '--projects' => 'AV-MNT-009',
        '--status' => 'banana',
        '--force' => true,
    ]);

    expect($exit)->toBe(1);
    expect(Phase::where('status', 'completed')->count())->toBe(0);
});
