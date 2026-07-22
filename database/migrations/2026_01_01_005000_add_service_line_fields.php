<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_service_lines', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('total');
            $table->date('planned_start')->nullable()->after('status');
            $table->date('planned_end')->nullable()->after('planned_start');
            $table->date('actual_start')->nullable()->after('planned_end');
            $table->date('actual_end')->nullable()->after('actual_start');
        });
    }

    public function down(): void
    {
        Schema::table('project_service_lines', function (Blueprint $table) {
            $table->dropColumn(['status', 'planned_start', 'planned_end', 'actual_start', 'actual_end']);
        });
    }
};
