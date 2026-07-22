<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->create('service_types', function ($table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('default_phase_templates')->nullable();
            $table->json('unit_rates')->nullable();
            $table->timestamps();
        });
    }

    public function down(Builder $schema)
    {
        $schema->dropIfExists('service_types');
    }
};
