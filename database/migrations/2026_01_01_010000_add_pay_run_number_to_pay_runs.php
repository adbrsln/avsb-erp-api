<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('pay_runs', function ($table) {
            $table->string('pay_run_number', 50)->nullable()->unique()->after('staff_id');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('pay_runs', function ($table) {
            $table->dropColumn('pay_run_number');
        });
    }
};
