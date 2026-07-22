<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('payroll_run_items', function ($table) {
            $table->decimal('socso_24h_employee', 10, 2)->default(0)->after('eis_employee');
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('payroll_run_items', function ($table) {
            $table->dropColumn('socso_24h_employee');
        });
    }
};
