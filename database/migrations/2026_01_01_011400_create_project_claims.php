<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->create('project_claims', function ($table) {
            $table->id();
            $table->string('claim_number', 50)->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('staff_profiles')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('staff_profiles')->nullOnDelete();
            $table->datetime('submitted_at')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->json('items')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(Builder $schema)
    {
        $schema->dropIfExists('project_claims');
    }
};
