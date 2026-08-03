<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\ChecklistItem;
use App\Models\ChecklistResult;
use App\Models\Contract;
use App\Models\ContractVariation;
use App\Models\Geofence;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Phase;
use App\Models\PhaseComment;
use App\Models\Project;
use App\Models\ProjectClaim;
use App\Models\ProjectClaimDocument;
use App\Models\ProjectDocument;
use App\Models\ProjectMaterialUsage;
use App\Models\ProjectSubcontractor;
use App\Models\Quotation;
use App\Models\Receipt;
use App\Models\SelfBilledInvoice;
use App\Models\SubcontractorClaim;
use App\Models\SubcontractorClaimDocument;
use App\Models\Task;
use App\Models\Timecard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetProjects extends Command
{
    protected $signature = 'app:reset-projects
        {--project= : Comma-separated project codes to reset (default: all projects)}
        {--status= : Only reset projects with this status (e.g. completed)}
        {--dry-run : Show what would be deleted without changing anything}
        {--force : Skip confirmation prompt}';

    protected $description = 'Hard-delete projects and ALL related data (invoices, contracts, quotes, phases, tasks, claims, attendance, documents, self-billed invoices, geofences, pivots)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $projectCodes = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('project')))));
        $status = $this->option('status');

        $query = Project::withTrashed();
        if ($status) {
            $query->where('status', $status);
        }
        if (! empty($projectCodes)) {
            $query->whereIn('project_code', $projectCodes);
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            $this->error('No projects matched the given criteria.');

            return Command::FAILURE;
        }

        if (! $dryRun && ! $force) {
            $this->warn('This will PERMANENTLY DELETE '.$projects->count().' project(s) and ALL related data. This cannot be undone.');
            if (! $this->confirm('Continue?')) {
                $this->info('Aborted.');

                return Command::SUCCESS;
            }
        }

        $label = $dryRun ? ' (DRY RUN — nothing will be changed)' : '';
        $this->info('Targeting '.$projects->count().' project(s): '.$projects->pluck('project_code')->implode(', ').$label."\n");

        $projectIds = $projects->pluck('id')->all();
        $phaseIds = Phase::whereIn('project_id', $projectIds)->pluck('id')->all();
        $taskIds = Task::whereIn('phase_id', $phaseIds)->pluck('id')->all();
        $claimIds = ProjectClaim::withTrashed()->whereIn('project_id', $projectIds)->pluck('id')->all();
        $contractIds = Contract::withTrashed()->whereIn('project_id', $projectIds)->pluck('id')->all();
        $invoiceIds = Invoice::withTrashed()->whereIn('project_id', $projectIds)->pluck('id')->all();
        $subconIds = ProjectSubcontractor::whereIn('project_id', $projectIds)->pluck('id')->all();
        $subconClaimIds = SubcontractorClaim::whereIn('project_subcontractor_id', $subconIds)->pluck('id')->all();

        $steps = [
            'subcontractor_claim_documents' => fn () => SubcontractorClaimDocument::whereIn('subcontractor_claim_id', $subconClaimIds)->count(),
            'subcontractor_claims' => fn () => SubcontractorClaim::whereIn('id', $subconClaimIds)->count(),
            'project_subcontractors' => fn () => ProjectSubcontractor::whereIn('id', $subconIds)->count(),
            'project_claim_documents' => fn () => ProjectClaimDocument::withTrashed()->whereIn('project_claim_id', $claimIds)->count(),
            'project_claims' => fn () => ProjectClaim::withTrashed()->whereIn('id', $claimIds)->count(),
            'contract_variations' => fn () => ContractVariation::whereIn('contract_id', $contractIds)->count(),
            'contracts' => fn () => Contract::withTrashed()->whereIn('id', $contractIds)->count(),
            'quotations' => fn () => Quotation::withTrashed()->whereIn('project_id', $projectIds)->count(),
            'invoice_payments' => fn () => InvoicePayment::whereIn('invoice_id', $invoiceIds)->count(),
            'receipts' => fn () => Receipt::whereIn('invoice_id', $invoiceIds)->count(),
            'invoices' => fn () => Invoice::withTrashed()->whereIn('id', $invoiceIds)->count(),
            'attendance' => fn () => Attendance::whereIn('project_id', $projectIds)->count(),
            'timecards' => fn () => Timecard::withTrashed()->whereIn('project_id', $projectIds)->count(),
            'activity_log' => fn () => ActivityLog::whereIn('project_id', $projectIds)->count(),
            'self_billed_invoices' => fn () => SelfBilledInvoice::withTrashed()->whereIn('project_id', $projectIds)->count(),
            'geofences' => fn () => Geofence::whereIn('project_id', $projectIds)->count(),
            'project_material_usage' => fn () => ProjectMaterialUsage::whereIn('project_id', $projectIds)->count(),
            'project_documents' => fn () => ProjectDocument::withTrashed()->whereIn('project_id', $projectIds)->count(),
            'task_staff' => fn () => DB::table('task_staff')->whereIn('task_id', $taskIds)->count(),
            'tasks' => fn () => Task::whereIn('id', $taskIds)->count(),
            'phase_staff' => fn () => DB::table('phase_staff')->whereIn('phase_id', $phaseIds)->count(),
            'phase_comments' => fn () => PhaseComment::whereIn('phase_id', $phaseIds)->count(),
            'checklist_results' => fn () => ChecklistResult::whereIn('phase_id', $phaseIds)->count(),
            'checklist_items' => fn () => ChecklistItem::whereIn('phase_id', $phaseIds)->count(),
            'project_phases' => fn () => Phase::whereIn('id', $phaseIds)->count(),
            'project_staff_pics' => fn () => DB::table('project_staff_pics')->whereIn('project_id', $projectIds)->count(),
            'project_project_type' => fn () => DB::table('project_project_type')->whereIn('project_id', $projectIds)->count(),
            'project_project_group' => fn () => DB::table('project_project_group')->whereIn('project_id', $projectIds)->count(),
            'projects' => fn () => count($projectIds),
        ];

        if ($dryRun) {
            foreach ($steps as $table => $countFn) {
                $this->line(sprintf('  %-30s %d', $table, $countFn()));
            }

            $this->newLine();
            $this->info('Dry run complete — nothing was deleted.');

            return Command::SUCCESS;
        }

        try {
            DB::beginTransaction();

            $deleted = [];

            $deleted['subcontractor_claim_documents'] = SubcontractorClaimDocument::withTrashed()->whereIn('subcontractor_claim_id', $subconClaimIds)->forceDelete();
            $deleted['subcontractor_claims'] = SubcontractorClaim::whereIn('id', $subconClaimIds)->delete();
            $deleted['project_subcontractors'] = ProjectSubcontractor::whereIn('id', $subconIds)->delete();
            $deleted['project_claim_documents'] = ProjectClaimDocument::withTrashed()->whereIn('project_claim_id', $claimIds)->forceDelete();
            $deleted['project_claims'] = ProjectClaim::withTrashed()->whereIn('id', $claimIds)->forceDelete();
            $deleted['contract_variations'] = ContractVariation::whereIn('contract_id', $contractIds)->delete();
            $deleted['contracts'] = Contract::withTrashed()->whereIn('id', $contractIds)->forceDelete();
            $deleted['quotations'] = Quotation::withTrashed()->whereIn('project_id', $projectIds)->forceDelete();
            $deleted['invoice_payments'] = InvoicePayment::whereIn('invoice_id', $invoiceIds)->delete();
            $deleted['receipts'] = Receipt::whereIn('invoice_id', $invoiceIds)->delete();
            $deleted['invoices'] = Invoice::withTrashed()->whereIn('id', $invoiceIds)->forceDelete();
            $deleted['attendance'] = Attendance::whereIn('project_id', $projectIds)->delete();
            $deleted['timecards'] = Timecard::withTrashed()->whereIn('project_id', $projectIds)->forceDelete();
            $deleted['activity_log'] = ActivityLog::whereIn('project_id', $projectIds)->delete();
            $deleted['self_billed_invoices'] = SelfBilledInvoice::withTrashed()->whereIn('project_id', $projectIds)->forceDelete();
            $deleted['geofences'] = Geofence::whereIn('project_id', $projectIds)->delete();
            $deleted['project_material_usage'] = ProjectMaterialUsage::whereIn('project_id', $projectIds)->delete();
            $deleted['project_documents'] = ProjectDocument::withTrashed()->whereIn('project_id', $projectIds)->forceDelete();
            $deleted['task_staff'] = DB::table('task_staff')->whereIn('task_id', $taskIds)->delete();
            $deleted['tasks'] = Task::whereIn('id', $taskIds)->delete();
            $deleted['phase_staff'] = DB::table('phase_staff')->whereIn('phase_id', $phaseIds)->delete();
            $deleted['phase_comments'] = PhaseComment::whereIn('phase_id', $phaseIds)->delete();
            $deleted['checklist_results'] = ChecklistResult::whereIn('phase_id', $phaseIds)->delete();
            $deleted['checklist_items'] = ChecklistItem::whereIn('phase_id', $phaseIds)->delete();
            $deleted['project_phases'] = Phase::whereIn('id', $phaseIds)->delete();
            $deleted['project_staff_pics'] = DB::table('project_staff_pics')->whereIn('project_id', $projectIds)->delete();
            $deleted['project_project_type'] = DB::table('project_project_type')->whereIn('project_id', $projectIds)->delete();
            $deleted['project_project_group'] = DB::table('project_project_group')->whereIn('project_id', $projectIds)->delete();
            $deleted['projects'] = Project::withTrashed()->whereIn('id', $projectIds)->forceDelete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Reset failed and was rolled back: '.$e->getMessage());

            return Command::FAILURE;
        }

        $total = array_sum($deleted);
        foreach ($deleted as $table => $count) {
            $this->line(sprintf('  %-30s %d deleted', $table, $count));
        }

        $this->newLine();
        $this->info("Done. Deleted {$projects->count()} project(s), {$total} related rows in total.");

        return Command::SUCCESS;
    }
}
