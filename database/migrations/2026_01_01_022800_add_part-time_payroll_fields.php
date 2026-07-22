<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payroll_run_items', 'wage_type')) {
            Schema::table('payroll_run_items', function (Blueprint $table) {
                $table->string('wage_type', 20)->default('monthly_salary')->after('payroll_run_id');
                $table->decimal('total_hours', 6, 2)->nullable()->after('salary');
                $table->decimal('hourly_rate_applied', 10, 2)->nullable()->after('total_hours');
                $table->date('period_start')->nullable()->after('hourly_rate_applied');
                $table->date('period_end')->nullable()->after('period_start');
            });
        }

        if (! Schema::hasColumn('attendance', 'payroll_run_item_id')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->unsignedBigInteger('payroll_run_item_id')->nullable()->after('note');
                $table->foreign('payroll_run_item_id')
                    ->references('id')
                    ->on('payroll_run_items')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendance', 'payroll_run_item_id')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->dropForeign(['payroll_run_item_id']);
                $table->dropColumn('payroll_run_item_id');
            });
        }

        if (Schema::hasColumn('payroll_run_items', 'wage_type')) {
            Schema::table('payroll_run_items', function (Blueprint $table) {
                $table->dropColumn(['wage_type', 'total_hours', 'hourly_rate_applied', 'period_start', 'period_end']);
            });
        }
    }
};
