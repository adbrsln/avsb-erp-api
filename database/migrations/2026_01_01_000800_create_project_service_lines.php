<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('project_service_lines', function ($table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->string('unit')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('unit_rate', 10, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->dropIfExists('project_service_lines');
    }
};
