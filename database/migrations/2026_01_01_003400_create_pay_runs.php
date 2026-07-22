<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('pay_runs', function ($table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_hours', 6, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('gross_pay', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('net_pay', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->datetime('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->dropIfExists('pay_runs');
    }
};
