<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->create('project_type_phase_template', function ($table) {
            $table->foreignId('project_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phase_template_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->unique(['project_type_id', 'phase_template_id'], 'pt_pt_unique');
        });
    }

    public function down(Builder $schema): void
    {
        $schema->dropIfExists('project_type_phase_template');
    }
};
