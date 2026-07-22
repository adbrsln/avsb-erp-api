<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('started_by')->nullable()->constrained('staff_profiles')->nullOnDelete()->after('actual_start');
            $table->foreignId('paused_by')->nullable()->constrained('staff_profiles')->nullOnDelete()->after('paused_at');
            $table->foreignId('completed_by')->nullable()->constrained('staff_profiles')->nullOnDelete()->after('actual_end');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['started_by']);
            $table->dropForeign(['paused_by']);
            $table->dropForeign(['completed_by']);
            $table->dropColumn(['started_by', 'paused_by', 'completed_by']);
        });
    }
};
