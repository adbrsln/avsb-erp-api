<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('role')->nullable();
            $table->string('job_title')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('basic_salary', 10, 2)->nullable();
            $table->string('epf_no')->nullable();
            $table->string('socso_no')->nullable();
            $table->date('date_joined')->nullable();
            $table->json('spouse')->nullable();
            $table->json('address')->nullable();
            $table->json('emergency_contact')->nullable();
            $table->json('dependent_children')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};
