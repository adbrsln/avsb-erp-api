<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('invoices', function ($table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client')->nullable();
            $table->date('date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('sst', 12, 2)->default(0);
            $table->decimal('retention', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->datetime('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->dropIfExists('invoices');
    }
};
