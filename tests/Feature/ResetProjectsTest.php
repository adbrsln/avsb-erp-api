<?php

use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Geofence;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectClaim;
use App\Models\Quotation;
use App\Models\SelfBilledInvoice;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\artisan;

function resetProjectFixture(string $code, string $status = 'active'): Project
{
    $project = Project::create([
        'name' => 'Reset Fixture '.$code,
        'project_code' => $code,
        'client' => 'Reset Test Client',
        'status' => $status,
        'budget_amount' => 100000,
        'latitude' => 3.139,
        'longitude' => 101.6869,
        'radius_meters' => 100,
    ]);

    $phase = Phase::create([
        'project_id' => $project->id,
        'name' => 'Fixture Phase',
        'order' => 1,
        'status' => 'completed',
    ]);

    Task::create([
        'phase_id' => $phase->id,
        'title' => 'Fixture Task',
        'status' => 'completed',
    ]);

    Invoice::create([
        'invoice_number' => 'FIX-INV-'.$code,
        'project_id' => $project->id,
        'client' => 'Reset Test Client',
        'date' => now()->toDateString(),
        'status' => 'paid',
        'subtotal' => 1000,
        'sst' => 80,
        'retention' => 0,
        'total' => 1080,
        'source' => 'system',
    ]);

    Quotation::create([
        'quote_number' => 'FIX-QT-'.$code,
        'project_id' => $project->id,
        'client' => 'Reset Test Client',
        'date' => now()->toDateString(),
        'status' => 'sent',
        'subtotal' => 1000,
        'sst' => 80,
        'total' => 1080,
    ]);

    Contract::create([
        'contract_number' => 'FIX-CON-'.$code,
        'project_id' => $project->id,
        'client' => 'Reset Test Client',
        'status' => 'active',
        'total_amount' => 1080,
        'subtotal' => 1000,
        'sst_rate' => 8,
    ]);

    ProjectClaim::create([
        'project_id' => $project->id,
        'claim_number' => 'FIX-CLM-'.$code,
        'title' => 'Fixture Claim',
        'claim_date' => now()->toDateString(),
        'amount' => 500,
        'status' => 'paid',
    ]);

    Attendance::create([
        'staff_id' => 1,
        'date' => now()->toDateString(),
        'clock_in' => now(),
        'clock_out' => now(),
        'total_hours' => 8,
        'status' => 'present',
        'project_id' => $project->id,
    ]);

    Geofence::create([
        'project_id' => $project->id,
        'name' => 'Reset Fixture Site '.$code,
        'latitude' => 3.139,
        'longitude' => 101.6869,
        'radius_meters' => 100,
        'is_active' => true,
    ]);

    return $project;
}

beforeEach(function () {
    // Remove seeded "Test Project" so fixture-count assertions are predictable.
    Project::withTrashed()->where('project_code', 'TEST-PRJ-001')->forceDelete();
    Project::withTrashed()->where('name', 'Reset Fixture %')->get()->each->forceDelete();
});

describe('app:reset-projects', function () {

    it('dry-run deletes nothing', function () {
        $project = resetProjectFixture('DRY-001');

        artisan('app:reset-projects', ['--project' => 'DRY-001', '--dry-run' => true])
            ->assertSuccessful();

        expect(Project::withTrashed()->find($project->id))->not->toBeNull();
        expect(Invoice::withTrashed()->where('project_id', $project->id)->exists())->toBeTrue();
        expect(Phase::where('project_id', $project->id)->exists())->toBeTrue();
    });

    it('deletes a single project and all related rows', function () {
        $project = resetProjectFixture('ONE-001');
        $other = resetProjectFixture('TWO-002');

        artisan('app:reset-projects', ['--project' => 'ONE-001', '--force' => true])
            ->assertSuccessful();

        // Target fully gone
        expect(Project::withTrashed()->find($project->id))->toBeNull();
        expect(Invoice::withTrashed()->where('project_id', $project->id)->exists())->toBeFalse();
        expect(Quotation::withTrashed()->where('project_id', $project->id)->exists())->toBeFalse();
        expect(Contract::withTrashed()->where('project_id', $project->id)->exists())->toBeFalse();
        expect(ProjectClaim::withTrashed()->where('project_id', $project->id)->exists())->toBeFalse();
        expect(Phase::where('project_id', $project->id)->exists())->toBeFalse();
        expect(Task::where('project_id', $project->id)->exists())->toBeFalse();
        expect(Attendance::where('project_id', $project->id)->exists())->toBeFalse();
        expect(Geofence::where('project_id', $project->id)->exists())->toBeFalse();

        // Other project untouched
        expect(Project::withTrashed()->find($other->id))->not->toBeNull();
        expect(Invoice::withTrashed()->where('project_id', $other->id)->exists())->toBeTrue();
        expect(Phase::where('project_id', $other->id)->exists())->toBeTrue();
    });

    it('filters by status', function () {
        $active = resetProjectFixture('STA-001', 'active');
        $completed = resetProjectFixture('STC-001', 'completed');

        artisan('app:reset-projects', ['--status' => 'completed', '--force' => true])
            ->assertSuccessful();

        expect(Project::withTrashed()->find($completed->id))->toBeNull();
        expect(Project::withTrashed()->find($active->id))->not->toBeNull();
    });

    it('deletes self_billed invoices despite RESTRICT FK', function () {
        $project = resetProjectFixture('SBI-001');

        $supplierId = DB::table('clients')->value('id') ?? 1;

        SelfBilledInvoice::create([
            'invoice_number' => 'FIX-SBI-001',
            'supplier_id' => $supplierId,
            'project_id' => $project->id,
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'paid',
            'subtotal' => 1000,
            'sst' => 80,
            'retention' => 0,
            'total' => 1080,
        ]);

        artisan('app:reset-projects', ['--project' => 'SBI-001', '--force' => true])
            ->assertSuccessful();

        expect(Project::withTrashed()->find($project->id))->toBeNull();
        expect(SelfBilledInvoice::withTrashed()->where('project_id', $project->id)->exists())->toBeFalse();
    });

    it('leaves no orphan tasks behind', function () {
        $project = resetProjectFixture('ORPH-001');

        artisan('app:reset-projects', ['--project' => 'ORPH-001', '--force' => true])
            ->assertSuccessful();

        // No task anywhere references a phase of the deleted project.
        expect(Task::whereIn('phase_id', Phase::where('project_id', $project->id)->pluck('id'))->count())->toBe(0);
        expect(DB::table('task_staff')->count())->toBe(0);
    });

    it('errors when no project matches', function () {
        artisan('app:reset-projects', ['--project' => 'NO-SUCH-CODE', '--force' => true])
            ->assertExitCode(1);
    });

    it('removes pivot rows', function () {
        $project = resetProjectFixture('PIVOT-001');

        $projectTypeId = DB::table('project_types')->insertGetId([
            'name' => 'Reset Test Type',
            'code' => 'RESET-TYPE',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $projectGroupId = DB::table('project_groups')->insertGetId([
            'name' => 'Reset Test Group',
            'color' => '#6b7280',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('project_staff_pics')->insert(['project_id' => $project->id, 'staff_id' => 1]);
        DB::table('project_project_type')->insert(['project_id' => $project->id, 'project_type_id' => $projectTypeId]);
        DB::table('project_project_group')->insert(['project_id' => $project->id, 'project_group_id' => $projectGroupId]);

        artisan('app:reset-projects', ['--project' => 'PIVOT-001', '--force' => true])
            ->assertSuccessful();

        expect(DB::table('project_staff_pics')->where('project_id', $project->id)->exists())->toBeFalse();
        expect(DB::table('project_project_type')->where('project_id', $project->id)->exists())->toBeFalse();
        expect(DB::table('project_project_group')->where('project_id', $project->id)->exists())->toBeFalse();
    });

    it('deletes orphan journal entries referencing invoices', function () {
        $project = resetProjectFixture('JE-001');
        $invoice = Invoice::withTrashed()->where('project_id', $project->id)->first();

        // Simulate issue JE + payment JE for the fixture invoice
        $issueJe = JournalEntry::create([
            'entry_number' => 'JE-FIX-001',
            'entry_date' => now()->toDateString(),
            'description' => 'Invoice issued - '.$invoice->invoice_number,
            'reference_type' => 'invoice',
            'reference_id' => $invoice->id,
            'status' => 'posted',
            'posted_at' => now(),
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $issueJe->id,
            'account_id' => 1,
            'debit' => 1000,
            'description' => $invoice->invoice_number,
        ]);

        $payJe = JournalEntry::create([
            'entry_number' => 'JE-FIX-002',
            'entry_date' => now()->toDateString(),
            'description' => 'Payment received - '.$invoice->invoice_number,
            'reference_type' => 'payment',
            'reference_id' => $invoice->id,
            'status' => 'posted',
            'posted_at' => now(),
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $payJe->id,
            'account_id' => 1,
            'credit' => 1000,
            'description' => $invoice->invoice_number,
        ]);

        artisan('app:reset-projects', ['--project' => 'JE-001', '--force' => true])
            ->assertSuccessful();

        expect(JournalEntry::whereIn('id', [$issueJe->id, $payJe->id])->exists())->toBeFalse();
        expect(JournalEntryLine::whereIn('journal_entry_id', [$issueJe->id, $payJe->id])->exists())->toBeFalse();
        expect(JournalEntry::count())->toBe(0);
    });

});
