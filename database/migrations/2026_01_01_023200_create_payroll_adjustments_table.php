<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->create('payroll_adjustments', function ($table) {
            $table->id();
            $table->foreignId('payroll_run_item_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['earnings', 'deductions']);
            $table->string('label', 255);
            $table->decimal('amount', 10, 2);
            $table->foreignId('created_by')->constrained('staff_profiles')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(Builder $schema)
    {
        $schema->dropIfExists('payroll_adjustments');
    }
};
