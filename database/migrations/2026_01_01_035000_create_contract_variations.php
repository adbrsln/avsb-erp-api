<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        if (! $schema->hasTable('contract_variations')) {
            $schema->create('contract_variations', function ($table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->string('variation_number', 50);
                $table->text('description');
                $table->decimal('amount', 12, 2)->default(0);
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('approved_by')->nullable()->constrained('staff_profiles')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(Builder $schema): void
    {
        $schema->dropIfExists('contract_variations');
    }
};
