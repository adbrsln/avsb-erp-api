<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Payment reference on expense claims
        if (! Schema::hasColumn('claims', 'payment_reference')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->string('payment_reference', 100)->nullable()->after('paid_at');
            });
        }

        // Paid at + payment reference on project claims
        if (! Schema::hasColumn('project_claims', 'paid_at')) {
            Schema::table('project_claims', function (Blueprint $table) {
                $table->dateTime('paid_at')->nullable()->after('approved_at');
                $table->string('payment_reference', 100)->nullable()->after('paid_at');
            });
        }

        // Paid by on payroll run items
        if (! Schema::hasColumn('payroll_run_items', 'paid_by')) {
            Schema::table('payroll_run_items', function (Blueprint $table) {
                $table->unsignedBigInteger('paid_by')->nullable()->after('paid_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('claims', 'payment_reference')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->dropColumn('payment_reference');
            });
        }

        if (Schema::hasColumn('project_claims', 'paid_at')) {
            Schema::table('project_claims', function (Blueprint $table) {
                $table->dropColumn('paid_at');
                $table->dropColumn('payment_reference');
            });
        }

        if (Schema::hasColumn('payroll_run_items', 'paid_by')) {
            Schema::table('payroll_run_items', function (Blueprint $table) {
                $table->dropColumn('paid_by');
            });
        }
    }
};
