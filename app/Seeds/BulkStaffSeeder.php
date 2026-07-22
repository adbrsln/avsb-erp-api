<?php

namespace App\Seeds;

use App\Helpers\MalaysianDataGenerator as G;
use App\Models\StaffLeaveBalance;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Capsule\Manager as Capsule;

class BulkStaffSeeder
{
    public function run(): void
    {
        if (StaffProfile::count() > 0 && StaffProfile::count() < 150) {
            echo "  [BulkStaffSeeder] Skipped: existing data found. Use --bulk with fresh-start.\n";

            return;
        }

        Capsule::connection()->statement('SET FOREIGN_KEY_CHECKS = 0');
        StaffProfile::truncate();
        User::truncate();
        UserRole::truncate();
        Capsule::connection()->statement('SET FOREIGN_KEY_CHECKS = 1');

        $staffBatch = G::generateStaffBatch(150);

        foreach ($staffBatch as $data) {
            $profile = StaffProfile::create($data);

            // Create leave balances for each staff
            $types = ['annual' => 14, 'medical' => 14, 'emergency' => 3, 'unpaid' => 0, 'marriage' => 3];
            foreach ($types as $type => $days) {
                $used = rand(0, min(5, $days));
                StaffLeaveBalance::create([
                    'staff_id' => $profile->id,
                    'type' => $type,
                    'year' => date('Y'),
                    'entitled' => $days,
                    'used' => $used,
                    'adjusted' => 0,
                    'balance' => $days - $used,
                ]);
            }
        }

        // Create users for each staff
        $rolePool = ['staff', 'staff', 'staff', 'staff', 'staff', 'pm', 'pm', 'hr', 'admin'];
        foreach (StaffProfile::all() as $s) {
            $user = User::firstOrCreate(
                ['email' => $s->email],
                [
                    'name' => $s->name,
                    'password' => password_hash('password123', PASSWORD_BCRYPT),
                ]
            );
            $role = $rolePool[array_rand($rolePool)];
            $user->syncRoles([$role]);
        }

        // Ensure admin and super_admin accounts exist
        $adminUser = User::firstOrCreate(
            ['email' => 'azamvsb@gmail.com'],
            ['name' => 'System Administrator', 'password' => password_hash('password123', PASSWORD_BCRYPT)]
        );
        $adminUser->syncRoles(['admin']);

        // Create staff profile for super_admin so getStaffId() resolves correctly
        StaffProfile::firstOrCreate(
            ['email' => 'superadmin@azamventures.com'],
            [
                'name' => 'Super Admin',
                'employee_id' => 'AD-001',
                'role' => 'super_admin',
                'job_title' => 'System Administrator',
                'is_active' => true,
                'basic_salary' => 0,
                'hourly_rate' => 0,
                'date_of_birth' => '2000-01-01',
                'identification_no' => '000000-00-0000',
                'phone' => '012-0000000',
                'gender' => 'male',
                'race' => 'Other',
                'nationality' => 'Malaysian',
                'residential_status' => 'resident',
                'marital_status' => 'single',
                'ability_status' => 'normal',
                'has_pr' => false,
                'hire_date' => date('Y-m-d'),
                'joined_at' => date('Y-m-d H:i:s'),
                'salary_wage_frequency' => 'monthly',
                'payment_method' => 'bank_transfer',
                'bank_name' => 'Maybank',
                'bank_account_no' => '0000-0000-0000',
                'epf_contributing' => true,
                'eis_contributing' => false,
                'payroll_policy' => 'standard',
                'payroll_cycle' => 'monthly',
                'date_joined' => date('Y-m-d'),
                'address' => ['line1' => 'HQ', 'city' => 'Kuala Lumpur', 'state' => 'WP Kuala Lumpur', 'postcode' => '50000'],
                'emergency_contact' => null,
                'dependent_children' => [],
                'spouse' => null,
                'worker_status' => 'probation',
                'department' => 'Administration',
                'location' => 'HQ',
                'schedule' => 'Mon-Fri 9am-6pm',
                'epf_member_before_aug_1998' => false,
                'pcb_borne_by_employer' => false,
                'socso_contribution_type' => 'full',
                'reported_to_lhdn' => true,
                'epf_voluntary_employee_rate' => null,
                'epf_voluntary_employer_rate' => null,
            ]
        );

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@azamventures.com'],
            ['name' => 'Super Admin', 'password' => password_hash('password123', PASSWORD_BCRYPT)]
        );
        $superAdmin->syncRoles(['super_admin']);
    }
}
