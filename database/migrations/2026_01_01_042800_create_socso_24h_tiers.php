<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->create('socso_24h_tiers', function ($table) {
            $table->id();
            $table->string('category', 10);
            $table->integer('phase')->default(1);
            $table->decimal('wage_from', 10, 2);
            $table->decimal('wage_to', 10, 2)->nullable();
            $table->decimal('employee_amount', 10, 2);
            $table->index(['category', 'phase']);
        });
    }

    public function down(Builder $schema): void
    {
        $schema->dropIfExists('socso_24h_tiers');
    }
};
