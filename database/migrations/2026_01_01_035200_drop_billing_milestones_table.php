<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->dropIfExists('billing_milestones');
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        if (!$schema->hasTable('billing_milestones')) {
            $schema->create('billing_milestones', function ($table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->string('description');
                $table->decimal('percentage', 5, 2);
                $table->decimal('amount', 12, 2);
                $table->date('due_date')->nullable();
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }
};
