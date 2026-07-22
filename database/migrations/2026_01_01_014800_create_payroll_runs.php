<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('payroll_runs', function ($table) {
            $table->id();
            $table->foreignId('period_id')->constrained('payroll_periods')->restrictOnDelete();
            $table->datetime('processed_at');
            $table->string('status', 20)->default('processing')->comment('processing|completed|failed');
            $table->unsignedInteger('total_employees')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->dropIfExists('payroll_runs');
    }
};
