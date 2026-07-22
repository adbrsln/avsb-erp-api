<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->create('phase_staff', function ($table) {
            $table->foreignId('phase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->primary(['phase_id', 'staff_id']);
        });
    }

    public function down(Builder $schema)
    {
        $schema->dropIfExists('phase_staff');
    }
};
