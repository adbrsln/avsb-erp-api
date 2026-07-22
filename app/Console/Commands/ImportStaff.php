<?php

namespace App\Console\Commands;

use App\Models\LeaveGroup;
use App\Models\StaffLeaveBalance;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Console\Command;

class ImportStaff extends Command
{
    protected $signature = 'cron:import-staff
        {--file= : Path to CSV file (default: database/data/avsb-staff-migration.csv)}
        {--dry-run : Preview only, no changes made}
        {--force : Skip confirmation prompt}';

    protected $description = 'Import staff from CSV — creates/updates StaffProfile and User records';

    public function handle(): int
    {
        $filePath = $this->option('file') ?: database_path('data/avsb-staff-migration.csv');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return Command::FAILURE;
        }

        // Confirmation
        if (! $force && ! $dryRun) {
            $this->warn("This will import/update staff records from: {$filePath}");
            if (! $this->confirm('Continue?')) {
                $this->info('Aborted.');

                return Command::SUCCESS;
            }
        }

        $label = $dryRun ? ' (DRY RUN — no changes will be made)' : '';
        $this->info("Importing staff from {$filePath}{$label}...\n");

        // Parse CSV
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            $this->error("Failed to open file: {$filePath}");

            return Command::FAILURE;
        }

        $headers = fgetcsv($handle, 0, ',', '"', '\\');
        $expectedCount = count($headers);

        $rowNum = 1;
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $rowNum++;

            while (count($row) < $expectedCount) {
                $row[] = '';
            }

            $data = array_combine($headers, $row);

            $name = trim($data['name'] ?? '');
            $email = trim($data['email'] ?? '');
            $tempEmail = trim($data['temp_email'] ?? '');
            $phone = trim($data['phone'] ?? '');

            if (empty($name)) {
                $this->line("  Row {$rowNum}: skipped (empty name)");
                $skipped++;

                continue;
            }

            $staffEmail = (! empty($email) && $email !== '-') ? $email : $tempEmail;
            if (empty($staffEmail)) {
                $this->line("  Row {$rowNum} ({$name}): skipped (no email)");
                $skipped++;

                continue;
            }

            $employeeId = 'EMP-'.str_pad((string) $rowNum, 3, '0', STR_PAD_LEFT);

            $profileData = [
                'name' => $name,
                'email' => $staffEmail,
                'alternate_email' => (! empty($tempEmail) && $tempEmail !== $staffEmail) ? $tempEmail : null,
                'phone' => $phone,
                'identification_no' => trim($data['identification_no'] ?? '') ?: null,
                'employee_id' => $employeeId,
                'job_title' => trim($data['job_title'] ?? ''),
                'is_active' => filter_var($data['is_active'] ?? 'TRUE', FILTER_VALIDATE_BOOLEAN),
                'department' => trim($data['department'] ?? ''),
                'schedule' => trim($data['schedule'] ?? ''),
                'date_joined' => trim($data['date_joined'] ?? '') ?: null,
                'hire_date' => trim($data['hire_date'] ?? '') ?: null,
                'joined_at' => trim($data['joined_at'] ?? '') ?: null,
                'date_of_birth' => trim($data['date_of_birth'] ?? '') ?: null,
                'gender' => strtolower(trim($data['gender'] ?? '')),
                'race' => trim($data['race'] ?? ''),
                'nationality' => trim($data['nationality'] ?? ''),
                'citizenship' => strtolower(trim($data['citizenship'] ?? '')) ?: null,
                'marital_status' => trim($data['marital_status'] ?? ''),
                'residential_status' => trim($data['residential_status'] ?? ''),
                'has_pr' => filter_var($data['has_pr'] ?? 'FALSE', FILTER_VALIDATE_BOOLEAN),
                'basic_salary' => $this->parseFloat($data['basic_salary'] ?? ''),
                'hourly_rate' => $this->parseFloat($data['hourly_rate'] ?? ''),
                'salary_wage_frequency' => trim($data['salary_wage_frequency'] ?? '') ?: 'monthly',
                'payment_method' => trim($data['payment_method'] ?? '') ?: 'bank_transfer',
                'bank_name' => trim($data['bank_name'] ?? ''),
                'bank_account_no' => trim($data['bank_account_no'] ?? ''),
                'account_name' => trim($data['account_name'] ?? ''),
                'epf_no' => trim($data['epf_no'] ?? '') ?: null,
                'socso_no' => trim($data['socso_no'] ?? '') ?: null,
                'ability_status' => 'normal',
                'worker_status' => 'normal',
                'epf_contributing' => ! empty(trim($data['epf_no'] ?? '')),
                'epf_member_before_aug_1998' => false,
                'epf_voluntary_employee_rate' => null,
                'epf_voluntary_employer_rate' => null,
                'pcb_borne_by_employer' => false,
                'socso_contribution_type' => 'full',
                'eis_contributing' => true,
                'reported_to_lhdn' => true,
                'payroll_policy' => 'standard',
                'payroll_cycle' => 'monthly',
                'location' => null,
                'spouse' => null,
                'address' => null,
                'emergency_contact' => null,
                'dependent_children' => [],
                'leave_group_id' => null,
            ];

            $roles = $this->parseRoles($data['sys_roles'] ?? '');
            $gender = strtolower(trim($data['gender'] ?? ''));
            $leaveGroupName = match ($gender) {
                'female' => 'Malaysian Standard - Female',
                'male' => 'Malaysian Standard Male',
                default => null,
            };

            if ($dryRun) {
                $leaveLabel = $leaveGroupName ? " leave_group={$leaveGroupName}" : '';
                $this->line("  [DRY RUN] Row {$rowNum}: {$name} <{$staffEmail}> roles=".json_encode($roles)."{$leaveLabel}");
                $imported++;

                continue;
            }

            try {
                $profile = StaffProfile::updateOrCreate(
                    ['email' => $staffEmail],
                    $profileData
                );

                if ($leaveGroupName) {
                    $group = LeaveGroup::with('entitlements')->where('name', $leaveGroupName)->first();
                    if ($group) {
                        $profile->leave_group_id = $group->id;
                        $profile->save();

                        $year = date('Y');
                        $currentTypes = $group->entitlements->pluck('type')->all();
                        StaffLeaveBalance::where('staff_id', $profile->id)
                            ->where('year', $year)
                            ->whereNotIn('type', $currentTypes)
                            ->delete();

                        foreach ($group->entitlements as $ent) {
                            StaffLeaveBalance::updateOrCreate(
                                ['staff_id' => $profile->id, 'type' => $ent->type, 'year' => $year],
                                [
                                    'entitled' => $ent->days_entitled,
                                    'used' => 0,
                                    'adjusted' => 0,
                                    'balance' => $ent->days_entitled,
                                ]
                            );
                        }
                    }
                }

                $user = User::firstOrCreate(
                    ['email' => $staffEmail],
                    [
                        'name' => $name,
                        'password' => password_hash('password123', PASSWORD_BCRYPT),
                    ]
                );

                if (! empty($roles)) {
                    $user->syncRoles($roles);
                }

                $leaveAssigned = $profile->leave_group_id ? ' leave_group='.($group->name ?? '') : '';
                $this->line("  Row {$rowNum}: {$name} <{$staffEmail}> — profile_id={$profile->id} user_id={$user->id} roles=".json_encode($roles)."{$leaveAssigned}");
                $imported++;
            } catch (\Throwable $e) {
                $this->line("  Row {$rowNum}: {$name} — ERROR: {$e->getMessage()}");
                $errors++;
            }
        }

        fclose($handle);

        $this->newLine();
        $this->info("Done. Imported: {$imported}, Skipped: {$skipped}, Errors: {$errors}");
        if ($dryRun) {
            $this->info('(Dry run — no data was modified)');
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function parseFloat(string $value): ?float
    {
        $v = trim($value);

        return $v === '' ? null : (float) $v;
    }

    private function parseRoles(string $value): array
    {
        $parts = explode(',', $value);

        return array_values(array_unique(array_filter(array_map(function ($r) {
            $r = strtolower(trim($r));

            return match ($r) {
                'owner' => 'admin',
                'foreman' => 'staff',
                default => $r,
            };
        }, $parts))));
    }
}
