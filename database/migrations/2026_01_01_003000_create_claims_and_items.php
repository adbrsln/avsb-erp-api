<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->create('claims', function ($table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->date('submitted_date')->nullable();
            $table->foreignId('approver_id')->nullable()->constrained('staff_profiles')->nullOnDelete();
            $table->datetime('approved_at')->nullable();
            $table->json('items')->nullable();
            $table->timestamps();
        });

        $schema->create('claim_items', function ($table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('claims')->cascadeOnDelete();
            $table->string('description');
            $table->string('category')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('receipt_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(Builder $schema)
    {
        $schema->dropIfExists('claim_items');
        $schema->dropIfExists('claims');
    }
};
