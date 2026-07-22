<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        if (! $schema->hasColumn('payroll_run_items', 'wage_type')) {
            $schema->table('payroll_run_items', function ($table) {
                $table->string('wage_type', 20)->default('monthly_salary')->after('payroll_run_id');
                $table->decimal('total_hours', 6, 2)->nullable()->after('salary');
                $table->decimal('hourly_rate_applied', 10, 2)->nullable()->after('total_hours');
                $table->date('period_start')->nullable()->after('hourly_rate_applied');
                $table->date('period_end')->nullable()->after('period_start');
            });
        }

        if (! $schema->hasColumn('attendance', 'payroll_run_item_id')) {
            $schema->table('attendance', function ($table) {
                $table->unsignedBigInteger('payroll_run_item_id')->nullable()->after('note');
                $table->foreign('payroll_run_item_id')
                    ->references('id')
                    ->on('payroll_run_items')
                    ->onDelete('set null');
            });
        }
    }

    public function down(Builder $schema): void
    {
        if ($schema->hasColumn('attendance', 'payroll_run_item_id')) {
            $schema->table('attendance', function ($table) {
                $table->dropForeign(['payroll_run_item_id']);
                $table->dropColumn('payroll_run_item_id');
            });
        }

        if ($schema->hasColumn('payroll_run_items', 'wage_type')) {
            $schema->table('payroll_run_items', function ($table) {
                $table->dropColumn(['wage_type', 'total_hours', 'hourly_rate_applied', 'period_start', 'period_end']);
            });
        }
    }
};
