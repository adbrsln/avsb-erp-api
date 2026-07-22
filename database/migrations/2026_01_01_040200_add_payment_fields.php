<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        // Payment reference on expense claims
        if (! $schema->hasColumn('claims', 'payment_reference')) {
            $schema->table('claims', function ($table) {
                $table->string('payment_reference', 100)->nullable()->after('paid_at');
            });
        }

        // Paid at + payment reference on project claims
        if (! $schema->hasColumn('project_claims', 'paid_at')) {
            $schema->table('project_claims', function ($table) {
                $table->dateTime('paid_at')->nullable()->after('approved_at');
                $table->string('payment_reference', 100)->nullable()->after('paid_at');
            });
        }

        // Paid by on payroll run items
        if (! $schema->hasColumn('payroll_run_items', 'paid_by')) {
            $schema->table('payroll_run_items', function ($table) {
                $table->unsignedBigInteger('paid_by')->nullable()->after('paid_at');
            });
        }
    }

    public function down(Builder $schema): void
    {
        if ($schema->hasColumn('claims', 'payment_reference')) {
            $schema->table('claims', function ($table) {
                $table->dropColumn('payment_reference');
            });
        }

        if ($schema->hasColumn('project_claims', 'paid_at')) {
            $schema->table('project_claims', function ($table) {
                $table->dropColumn('paid_at');
                $table->dropColumn('payment_reference');
            });
        }

        if ($schema->hasColumn('payroll_run_items', 'paid_by')) {
            $schema->table('payroll_run_items', function ($table) {
                $table->dropColumn('paid_by');
            });
        }
    }
};
