<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->dropIfExists('project_service_lines');
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->create('project_service_lines', function ($table) {
            $table->id();
            $table->string('service_line_ref', 50)->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 500);
            $table->string('unit', 50)->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('unit_rate', 12, 2)->nullable();
            $table->decimal('total', 14, 2)->nullable();
            $table->string('status', 20)->default('pending');
            $table->date('planned_start')->nullable();
            $table->date('planned_end')->nullable();
            $table->date('actual_start')->nullable();
            $table->date('actual_end')->nullable();
            $table->timestamps();
        });
    }
};
