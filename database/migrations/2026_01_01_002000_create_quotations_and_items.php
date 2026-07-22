<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('quotations', function ($table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client')->nullable();
            $table->date('date')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('sst', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('items')->nullable();
            $table->timestamps();
        });

        $schema->create('quotation_items', function ($table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
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
        $schema->dropIfExists('quotation_items');
        $schema->dropIfExists('quotations');
    }
};
