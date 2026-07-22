<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->table('pay_runs', function ($table) {
            $table->string('pay_run_number', 50)->nullable()->unique()->after('staff_id');
        });
    }

    public function down(Builder $schema)
    {
        $schema->table('pay_runs', function ($table) {
            $table->dropColumn('pay_run_number');
        });
    }
};
