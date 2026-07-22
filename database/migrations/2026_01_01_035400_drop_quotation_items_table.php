<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->dropIfExists('quotation_items');
    }

    public function down(Builder $schema): void
    {
        if (! $schema->hasTable('quotation_items')) {
            $schema->create('quotation_items', function ($table) {
                $table->id();
                $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
                $table->text('description');
                $table->string('unit')->nullable();
                $table->decimal('quantity', 10, 2)->default(0);
                $table->decimal('unit_rate', 10, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->timestamps();
            });
        }
    }
};
