<?php

namespace Database\Seeders;

use App\Models\StaffProfile;
use App\Models\User;

class StaffSeeder
{
    public function run(): void
    {
        if (StaffProfile::where('email', 'superadmin@azamventures.com')->exists()) {
            echo "  [StaffSeeder] Skipped: superadmin already exists.\n";

            return;
        }

        $staff = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@azamventures.com',
                'alternate_email' => null,
                'phone' => '012-3456793',
                'identification_no' => '920701-14-4422',
                'employee_id' => 'AD-001',
                'job_title' => 'Admin & Finance Officer',
                'is_active' => true,
                'hire_date' => '2023-01-01',
                'joined_at' => '2023-01-01 09:00:00',
                'worker_status' => 'probation',
                'department' => 'Administration',
                'location' => 'Kuala Lumpur HQ',
                'schedule' => 'Mon-Fri 9am-6pm',
                'date_of_birth' => '1992-07-01',
                'gender' => 'female',
                'race' => 'Other',
                'nationality' => 'Malaysian',
                'residential_status' => 'resident',
                'has_pr' => false,
                'marital_status' => 'single',
                'ability_status' => 'normal',
                'basic_salary' => 4200.00,
                'hourly_rate' => 22.00,
                'salary_wage_frequency' => 'monthly',
                'payment_method' => 'bank_transfer',
                'bank_name' => 'Hong Leong Bank',
                'bank_account_no' => '0032-5678-9012',
                'account_name' => 'Sarah Johnson',
                'epf_no' => 'EPF-920701-14-4422',
                'socso_no' => 'SOCSO-920701-14-4422',
                'tax_no' => 'SG-7778889999',
                'epf_contributing' => true,
                'epf_member_before_aug_1998' => false,
                'epf_voluntary_employee_rate' => null,
                'epf_voluntary_employer_rate' => null,
                'pcb_borne_by_employer' => false,
                'socso_contribution_type' => 'full',
                'eis_contributing' => true,
                'reported_to_lhdn' => true,
                'payroll_policy' => 'standard',
                'payroll_cycle' => 'monthly',
                'date_joined' => '2023-01-01',
                'spouse' => null,
                'address' => ['line1' => '22, Jalan Ampang', 'city' => 'Kuala Lumpur', 'state' => 'WP Kuala Lumpur', 'postcode' => '50450'],
                'emergency_contact' => ['name' => 'David Johnson', 'relation' => 'Brother', 'phone' => '012-9990011'],
                'dependent_children' => [],
            ],
        ];

        foreach ($staff as $s) {
            StaffProfile::create($s);
        }

        // Create user accounts with roles
        $userAccounts = [
            ['name' => 'Super Admin', 'email' => 'superadmin@azamventures.com', 'password' => 'password123', 'roles' => ['super_admin']],
        ];

        foreach ($userAccounts as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                ['name' => $u['name'], 'password' => password_hash($u['password'] ?? 'password123', PASSWORD_BCRYPT)]
            );
            $user->syncRoles($u['roles']);
        }
    }
}
