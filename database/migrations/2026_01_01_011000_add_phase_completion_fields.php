<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phases', function (Blueprint $table) {
            $table->foreignId('started_by')->nullable()->constrained('staff_profiles')->nullOnDelete()->after('status');
            $table->datetime('started_at')->nullable()->after('started_by');
            $table->foreignId('completed_by')->nullable()->constrained('staff_profiles')->nullOnDelete()->after('started_at');
            $table->datetime('completed_at')->nullable()->after('completed_by');
            $table->text('completion_remarks')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('phases', function (Blueprint $table) {
            $table->dropForeign(['started_by']);
            $table->dropForeign(['completed_by']);
            $table->dropColumn(['started_by', 'started_at', 'completed_by', 'completed_at', 'completion_remarks']);
        });
    }
};
