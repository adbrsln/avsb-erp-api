<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epf_contribution_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('schedule_code', 10);
            $table->decimal('wage_from', 10, 2);
            $table->decimal('wage_to', 10, 2);
            $table->decimal('employer_amount', 10, 2);
            $table->decimal('employee_amount', 10, 2);
            $table->timestamps();

            $table->foreign('schedule_code')->references('code')->on('epf_schedules')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unique(['schedule_code', 'wage_from', 'wage_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epf_contribution_tiers');
    }
};
