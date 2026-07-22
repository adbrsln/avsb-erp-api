<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epf_schedule_rules', function (Blueprint $table) {
            $table->id();
            $table->string('schedule_code', 10);
            $table->integer('min_age')->nullable();
            $table->integer('max_age')->nullable();
            $table->string('citizenship', 20)->nullable()->comment('citizen|pr|non_citizen|any');
            $table->string('is_pr', 5)->nullable()->comment('yes|no|any');
            $table->string('elected_before_1998', 5)->nullable()->comment('yes|no|any');
            $table->integer('priority')->default(0);

            $table->foreign('schedule_code')->references('code')->on('epf_schedules')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epf_schedule_rules');
    }
};
