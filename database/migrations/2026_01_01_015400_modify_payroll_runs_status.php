<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->table('payroll_runs', function ($table) {
            $table->string('status', 20)->default('draft')->comment('draft|completed|paid|failed')->change();
        });
    }

    public function down(Builder $schema)
    {
        $schema->table('payroll_runs', function ($table) {
            $table->string('status', 20)->default('processing')->comment('processing|completed|failed')->change();
        });
    }
};
