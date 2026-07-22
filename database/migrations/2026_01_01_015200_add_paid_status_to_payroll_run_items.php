<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->table('payroll_run_items', function ($table) {
            $table->boolean('paid')->default(false)->after('eis_employee');
            $table->datetime('paid_at')->nullable()->after('paid');
        });
    }

    public function down(Builder $schema)
    {
        $schema->table('payroll_run_items', function ($table) {
            $table->dropColumn(['paid', 'paid_at']);
        });
    }
};
