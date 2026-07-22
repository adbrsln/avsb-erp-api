<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->create('task_staff', function ($table) {
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->primary(['task_id', 'staff_id']);
        });
    }

    public function down(Builder $schema)
    {
        $schema->dropIfExists('task_staff');
    }
};
