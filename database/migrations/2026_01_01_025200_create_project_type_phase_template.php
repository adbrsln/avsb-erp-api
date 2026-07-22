<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_type_phase_template', function (Blueprint $table) {
            $table->foreignId('project_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phase_template_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->unique(['project_type_id', 'phase_template_id'], 'pt_pt_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_type_phase_template');
    }
};
