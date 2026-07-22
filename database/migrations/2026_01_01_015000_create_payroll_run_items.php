<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('staff_profiles')->restrictOnDelete();
            $table->decimal('salary', 10, 2);
            $table->decimal('epf_employer', 10, 2);
            $table->decimal('epf_employee', 10, 2);
            $table->string('epf_schedule_code', 10);
            $table->decimal('socso_employer', 10, 2);
            $table->decimal('socso_employee', 10, 2);
            $table->decimal('eis_employer', 10, 2);
            $table->decimal('eis_employee', 10, 2);

            $table->foreign('epf_schedule_code')->references('code')->on('epf_schedules')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_items');
    }
};
