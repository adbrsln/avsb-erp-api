<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('asset_services', function ($table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('service_type', 100);
            $table->date('service_date');
            $table->date('next_service_date')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('vendor', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('document_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->dropIfExists('asset_services');
    }
};
