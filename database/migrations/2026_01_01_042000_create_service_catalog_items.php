<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->create('service_catalog_items', function ($table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('unit', 50);
            $table->decimal('unit_rate', 12, 2)->default(0);
            $table->string('tax_code', 20)->nullable();
            $table->string('category', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(Builder $schema): void
    {
        $schema->dropIfExists('service_catalog_items');
    }
};
