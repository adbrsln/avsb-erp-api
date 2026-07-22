<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->table('payroll_run_items', function ($table) {
            $table->boolean('confirmed')->default(false)->after('paid_at');
            $table->datetime('confirmed_at')->nullable()->after('confirmed');
            $table->foreignId('confirmed_by')->nullable()->constrained('staff_profiles')->restrictOnDelete()->after('confirmed_at');
        });
    }

    public function down(Builder $schema)
    {
        $schema->table('payroll_run_items', function ($table) {
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn(['confirmed', 'confirmed_at']);
        });
    }
};
