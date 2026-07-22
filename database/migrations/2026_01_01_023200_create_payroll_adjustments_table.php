<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_item_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['earnings', 'deductions']);
            $table->string('label', 255);
            $table->decimal('amount', 10, 2);
            $table->foreignId('created_by')->constrained('staff_profiles')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
    }
};
