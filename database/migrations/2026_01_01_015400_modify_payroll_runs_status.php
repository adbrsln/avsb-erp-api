<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('payroll_runs', function ($table) {
            $table->string('status', 20)->default('draft')->comment('draft|completed|paid|failed')->change();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('payroll_runs', function ($table) {
            $table->string('status', 20)->default('processing')->comment('processing|completed|failed')->change();
        });
    }
};
