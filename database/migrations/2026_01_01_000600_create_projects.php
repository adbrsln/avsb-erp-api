<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('projects', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('client')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('draft');
            $table->string('contract_id')->nullable();
            $table->foreignId('service_type_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->dropIfExists('projects');
    }
};
