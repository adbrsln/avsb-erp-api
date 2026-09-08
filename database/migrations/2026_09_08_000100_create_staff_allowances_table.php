<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('amount', 10, 2);
            $table->enum('statutory_type', ['wages', 'additional', 'overtime', 'reimbursement']);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['staff_id', 'statutory_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_allowances');
    }
};
