<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('payroll_periods', function ($table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('open')->comment('open|closed');
            $table->timestamps();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->dropIfExists('payroll_periods');
    }
};
