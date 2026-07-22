<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        // 1. Create project_groups table
        $schema->create('project_groups', function ($table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6b7280');
            $table->integer('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Create pivot table
        $schema->create('project_project_group', function ($table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_group_id')->constrained('project_groups')->cascadeOnDelete();
            $table->unique(['project_id', 'project_group_id']);
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->dropIfExists('project_project_group');
        $schema->dropIfExists('project_groups');
    }
};
