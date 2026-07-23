<?php

namespace Database\Seeders;

use App\Models\StaffLeaveBalance;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\NumberingService;
use Illuminate\Support\Facades\DB;

class BulkStaffSeeder
{
    public function run(): void
    {
        if (StaffProfile::count() > 0 && StaffProfile::count() < 150) {
            echo "  [BulkStaffSeeder] Skipped: existing data found. Use --bulk with fresh-start.\n";

            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        StaffProfile::truncate();
        User::truncate();
        DB::table('user_roles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $numService = new NumberingService;

        $rolePool = ['staff', 'staff', 'staff', 'staff', 'staff', 'pm', 'pm', 'hr', 'admin'];
        $leaveTypes = ['annual' => 14, 'medical' => 14, 'emergency' => 3, 'unpaid' => 0, 'marriage' => 3];

        StaffProfile::factory()
            ->count(150)
            ->create()
            ->each(function (StaffProfile $profile) use ($rolePool, $leaveTypes) {
                $user = User::firstOrCreate(
                    ['email' => $profile->email],
                    [
                        'name' => $profile->name,
                        'password' => bcrypt('password123'),
                    ]
                );

                DB::table('user_roles')->insert(['user_id' => $user->id, 'role' => $rolePool[array_rand($rolePool)]]);

                foreach ($leaveTypes as $type => $days) {
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
            });

        $adminUser = User::firstOrCreate(
            ['email' => 'azamvsb@gmail.com'],
            ['name' => 'System Administrator', 'password' => bcrypt('password123')]
        );
        DB::table('user_roles')->insertOrIgnore(['user_id' => $adminUser->id, 'role' => 'admin']);

        StaffProfile::firstOrCreate(
            ['email' => 'superadmin@azamventures.com'],
            [
                'name' => 'Super Admin',
                'employee_id' => $numService->generate('staff'),
                'is_active' => true,
                'basic_salary' => 0,
                'gender' => 'male',
                'nationality' => 'Malaysian',
                'hire_date' => date('Y-m-d'),
                'salary_wage_frequency' => 'monthly',
                'payment_method' => 'bank_transfer',
                'bank_name' => 'Maybank',
                'bank_account_no' => '0000-0000-0000',
                'epf_contributing' => true,
                'payroll_policy' => 'standard',
                'payroll_cycle' => 'monthly',
                'department' => 'Administration',
                'location' => 'HQ',
                'schedule' => 'Mon-Fri 9am-6pm',
            ]
        );

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@azamventures.com'],
            ['name' => 'Super Admin', 'password' => bcrypt('password123')]
        );
        DB::table('user_roles')->insertOrIgnore(['user_id' => $superAdmin->id, 'role' => 'super_admin']);
    }
}
