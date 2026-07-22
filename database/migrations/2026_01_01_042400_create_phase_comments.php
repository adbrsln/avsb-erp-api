<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->create('phase_comments', function ($table) {
            $table->id();
            $table->foreignId('phase_id')->constrained('project_phases')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(Builder $schema): void
    {
        $schema->dropIfExists('phase_comments');
    }
};
