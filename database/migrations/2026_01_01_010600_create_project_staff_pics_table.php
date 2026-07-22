<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('project_staff_pics', function ($table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'staff_id']);
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->dropIfExists('project_staff_pics');
    }
};
