<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('epf_contribution_tiers', function ($table) {
            $table->id();
            $table->string('schedule_code', 10);
            $table->decimal('wage_from', 10, 2);
            $table->decimal('wage_to', 10, 2);
            $table->decimal('employer_amount', 10, 2);
            $table->decimal('employee_amount', 10, 2);
            $table->timestamps();

            $table->foreign('schedule_code')->references('code')->on('epf_schedules')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unique(['schedule_code', 'wage_from', 'wage_to']);
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->dropIfExists('epf_contribution_tiers');
    }
};
