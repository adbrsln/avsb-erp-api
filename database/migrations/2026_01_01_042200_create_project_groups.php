<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create project_groups table
        Schema::create('project_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6b7280');
            $table->integer('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Create pivot table
        Schema::create('project_project_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_group_id')->constrained('project_groups')->cascadeOnDelete();
            $table->unique(['project_id', 'project_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_project_group');
        Schema::dropIfExists('project_groups');
    }
};
