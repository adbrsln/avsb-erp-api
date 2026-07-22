<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->create('epf_schedule_rules', function ($table) {
            $table->id();
            $table->string('schedule_code', 10);
            $table->integer('min_age')->nullable();
            $table->integer('max_age')->nullable();
            $table->string('citizenship', 20)->nullable()->comment('citizen|pr|non_citizen|any');
            $table->string('is_pr', 5)->nullable()->comment('yes|no|any');
            $table->string('elected_before_1998', 5)->nullable()->comment('yes|no|any');
            $table->integer('priority')->default(0);

            $table->foreign('schedule_code')->references('code')->on('epf_schedules')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(Builder $schema)
    {
        $schema->dropIfExists('epf_schedule_rules');
    }
};
