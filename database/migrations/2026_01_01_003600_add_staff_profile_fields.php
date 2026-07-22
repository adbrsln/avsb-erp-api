<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->string('alternate_email')->nullable()->after('email');
            $table->string('identification_no', 50)->nullable()->after('phone');
            $table->string('employee_id', 50)->nullable()->after('identification_no');
            $table->date('hire_date')->nullable()->after('employee_id');
            $table->datetime('joined_at')->nullable()->after('hire_date');
            $table->date('last_day')->nullable()->after('joined_at');
            $table->text('archive_reason')->nullable()->after('last_day');
            $table->string('worker_status', 20)->nullable()->after('archive_reason');
            $table->string('department', 100)->nullable()->after('worker_status');
            $table->string('location', 255)->nullable()->after('department');
            $table->string('schedule', 100)->nullable()->after('location');
            $table->date('date_of_birth')->nullable()->after('schedule');
            $table->string('gender', 10)->nullable()->after('date_of_birth');
            $table->string('race', 50)->nullable()->after('gender');
            $table->string('nationality', 50)->nullable()->after('race');
            $table->string('residential_status', 20)->nullable()->after('nationality');
            $table->boolean('has_pr')->nullable()->after('residential_status');
            $table->string('marital_status', 20)->nullable()->after('has_pr');
            $table->string('ability_status', 20)->nullable()->after('marital_status');
            $table->decimal('hourly_rate', 8, 2)->nullable()->after('basic_salary');
            $table->string('salary_wage_frequency', 10)->nullable()->after('hourly_rate');
            $table->string('payment_method', 20)->nullable()->after('salary_wage_frequency');
            $table->string('bank_name', 100)->nullable()->after('payment_method');
            $table->string('bank_account_no', 50)->nullable()->after('bank_name');
            $table->string('account_name', 255)->nullable()->after('bank_account_no');
            $table->string('tax_no', 20)->nullable()->after('account_name');
            $table->boolean('epf_contributing')->nullable()->after('tax_no');
            $table->boolean('epf_member_before_aug_1998')->nullable()->after('epf_contributing');
            $table->decimal('epf_voluntary_employee_rate', 5, 2)->nullable()->after('epf_member_before_aug_1998');
            $table->decimal('epf_voluntary_employer_rate', 5, 2)->nullable()->after('epf_voluntary_employee_rate');
            $table->boolean('pcb_borne_by_employer')->nullable()->after('epf_voluntary_employer_rate');
            $table->string('socso_contribution_type', 50)->nullable()->after('pcb_borne_by_employer');
            $table->boolean('eis_contributing')->nullable()->after('socso_contribution_type');
            $table->boolean('reported_to_lhdn')->nullable()->after('eis_contributing');
            $table->string('payroll_policy', 100)->nullable()->after('reported_to_lhdn');
            $table->string('payroll_cycle', 50)->nullable()->after('payroll_policy');
        });
    }

    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'alternate_email', 'identification_no', 'employee_id',
                'hire_date', 'joined_at', 'last_day', 'archive_reason',
                'worker_status', 'department', 'location', 'schedule',
                'date_of_birth', 'gender', 'race', 'nationality',
                'residential_status', 'has_pr', 'marital_status', 'ability_status',
                'hourly_rate', 'salary_wage_frequency', 'payment_method',
                'bank_name', 'bank_account_no', 'account_name', 'tax_no',
                'epf_contributing', 'epf_member_before_aug_1998',
                'epf_voluntary_employee_rate', 'epf_voluntary_employer_rate',
                'pcb_borne_by_employer', 'socso_contribution_type',
                'eis_contributing', 'reported_to_lhdn',
                'payroll_policy', 'payroll_cycle',
            ]);
        });
    }
};
