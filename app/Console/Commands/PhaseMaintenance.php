<?php

namespace App\Console\Commands;

use App\Models\Phase;
use App\Models\Project;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('app:phase-maintenance
    {--status= : Target phase status: completed|pending}
    {--projects= : Comma-separated project codes (skip interactive selection)}
    {--po= : Set phases for one project matched by po_number}
    {--phase= : Restrict to a specific phase name (default: all phases)}
    {--force : Skip confirmation prompt}')]
#[Description('Bulk maintenance of project phases — set to pending or completed')]
class PhaseMaintenance extends Command
{
    public function handle(): int
    {
        $status = $this->resolveStatus();
        if ($status === null) {
            return Command::FAILURE;
        }

        $projects = $this->resolveProjects();
        if ($projects->isEmpty()) {
            $this->error('No matching projects found.');

            return Command::FAILURE;
        }

        $phaseName = $this->resolvePhaseName($projects);

        $this->renderSummary($projects, $status, $phaseName);
        if (! $this->option('force') && ! $this->confirm('Apply these phase changes?')) {
            $this->info('Aborted.');

            return Command::SUCCESS;
        }

        $changed = $this->apply($projects, $status, $phaseName);

        $this->newLine();
        $this->info("Done. {$changed} phase(s) updated to {$status}.");

        return Command::SUCCESS;
    }

    private function resolveStatus(): ?string
    {
        $status = (string) $this->option('status');
        if ($status === '') {
            if ($this->scripted()) {
                $this->error('--status is required when using selection flags.');

                return null;
            }
            $status = \Laravel\Prompts\select('Target phase status', ['completed', 'pending']);
        }
        if (! in_array($status, ['completed', 'pending'], true)) {
            $this->error('--status must be "completed" or "pending".');

            return null;
        }

        return $status;
    }

    /** Any selection/status flag present → non-interactive scripted run. */
    private function scripted(): bool
    {
        return (string) $this->option('status') !== ''
            || (string) $this->option('projects') !== ''
            || (string) $this->option('po') !== ''
            || (string) $this->option('phase') !== '';
    }

    /** @return Collection<int, Project> */
    private function resolveProjects(): Collection
    {
        $po = (string) $this->option('po');
        $codes = (string) $this->option('projects');

        if ($po !== '') {
            $project = Project::where('po_number', $po)->first();

            return $project ? collect([$project]) : collect();
        }

        if ($codes !== '') {
            $resolved = collect(explode(',', $codes))
                ->map(fn (string $c) => Project::where('project_code', trim($c))->first())
                ->filter()
                ->values();
            $this->warnUnresolved($codes, $resolved);

            return $resolved;
        }

        if ($this->scripted()) {
            $this->error('--projects or --po is required when using selection flags.');

            return collect();
        }

        $mode = \Laravel\Prompts\select('How do you want to select projects?', [
            'search' => 'Search projects (by code, name, or PO number)',
            'direct' => 'Enter project code(s) directly',
        ]);

        if ($mode === 'direct') {
            $input = \Laravel\Prompts\text('Project code(s), comma-separated');
            $codes = $input !== '' ? $input : '';
            $resolved = collect(explode(',', $codes))
                ->map(fn (string $c) => Project::where('project_code', trim($c))->first())
                ->filter()
                ->values();
            $this->warnUnresolved($codes, $resolved);

            return $resolved;
        }

        // Searchable bulk selection — type-ahead matches code/name/po_number
        return collect(array_keys(\Laravel\Prompts\multisearch(
            'Select projects (search by code, name, or PO number; space to select, enter to confirm)',
            fn (string $term) => $this->searchProjects($term),
            placeholder: 'Type to search…',
            scroll: 10,
        )))->map(fn (int $id) => Project::find($id))->filter()->values();
    }

    private function warnUnresolved(string $codes, Collection $resolved): void
    {
        $found = $resolved->pluck('project_code')->all();
        foreach (explode(',', $codes) as $code) {
            $code = trim($code);
            if ($code !== '' && ! in_array($code, $found, true)) {
                $this->warn("Project code not found: {$code}");
            }
        }
    }

    /** @return array<int, string> map of id => display label */
    private function searchProjects(string $term): array
    {
        $query = Project::query();
        $term = trim($term);
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $like = '%'.$term.'%';
                $q->where('project_code', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('po_number', 'like', $like);
            });
        }

        return $query->orderBy('project_code')->limit(50)->get()
            ->mapWithKeys(fn (Project $p) => [
                $p->id => $p->project_code.' — '.$p->name.' (PO: '.($p->po_number ?: '-').')',
            ])
            ->all();
    }

    /** @param  Collection<int, Project>  $projects */
    private function resolvePhaseName(Collection $projects): ?string
    {
        $option = (string) $this->option('phase');
        if ($option !== '' && $option !== 'All phases') {
            return $option;
        }
        if ($this->scripted()) {
            return null;
        }

        $names = Phase::whereIn('project_id', $projects->pluck('id'))
            ->select('name')->distinct()->orderBy('name')->pluck('name')->values();

        if ($names->isEmpty()) {
            return null;
        }
        $names->prepend('All phases', '__all__'); // Collection::prepend($value, $key) — key first is a bug

        $choice = \Laravel\Prompts\select('Which phases?', $names->all());

        return $choice === '__all__' ? null : $choice;
    }

    /** @param  Collection<int, Project>  $projects */
    private function renderSummary(Collection $projects, string $status, ?string $phaseName): void
    {
        $this->newLine();
        $this->info('Target: '.$status.($phaseName ? " (phase: {$phaseName})" : ' (all phases)'));
        foreach ($projects as $project) {
            $this->line('  '.$project->project_code.' — '.$this->targetPhaseCount($project, $phaseName).' phase(s)');
        }
        $this->newLine();
    }

    /** @param  Collection<int, Project>  $projects */
    private function apply(Collection $projects, string $status, ?string $phaseName): int
    {
        $changed = 0;
        $rows = [];

        foreach ($projects as $project) {
            $query = $project->phases();
            if ($phaseName !== null) {
                $query->where('name', $phaseName);
            }

            $perProject = 0;
            foreach ($query->get() as $phase) {
                if ($phase->status === $status) {
                    continue; // idempotent — already in target state
                }
                if ($status === 'completed') {
                    $phase->update([
                        'status' => 'completed',
                        'completed_at' => $phase->completed_at ?? date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $phase->update([
                        'status' => 'pending',
                        'completed_at' => null,
                        'completed_by' => null,
                        'started_at' => null,
                        'started_by' => null,
                    ]);
                }
                $changed++;
                $perProject++;
            }

            $rows[] = [$project->project_code, $perProject];
        }

        $this->table(['Project', 'Phases updated'], $rows);

        return $changed;
    }

    /** @param  Collection<int, Project>  $projects */
    private function targetPhaseCount(Project $project, ?string $phaseName): int
    {
        $query = $project->phases();
        if ($phaseName !== null) {
            $query->where('name', $phaseName);
        }

        return $query->count();
    }
}
