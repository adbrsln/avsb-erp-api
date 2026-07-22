<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
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

    public function down(Builder $schema)
    {
        $schema->dropIfExists('payroll_periods');
    }
};
