<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phase_staff', function (Blueprint $table) {
            $table->foreignId('phase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->primary(['phase_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phase_staff');
    }
};
