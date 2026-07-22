<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('attendance', 'flagged')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->boolean('flagged')->default(false);
            });
        }
        if (! Schema::hasColumn('attendance', 'flagged_reason')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->string('flagged_reason', 255)->nullable();
            });
        }
        if (! Schema::hasColumn('attendance', 'flagged_cleared_by')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->foreignId('flagged_cleared_by')->nullable()->constrained('staff_profiles')->restrictOnDelete();
            });
        }
        if (! Schema::hasColumn('attendance', 'flagged_cleared_at')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->datetime('flagged_cleared_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropConstrainedForeignId('flagged_cleared_by');
            $table->dropColumn(['flagged', 'flagged_reason', 'flagged_cleared_at']);
        });
    }
};
