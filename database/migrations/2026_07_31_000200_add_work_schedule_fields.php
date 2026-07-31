<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('company_settings', 'work_start_time')) {
                $table->time('work_start_time')->nullable();
            }
            if (! Schema::hasColumn('company_settings', 'work_end_time')) {
                $table->time('work_end_time')->nullable();
            }
        });

        Schema::table('staff_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('staff_profiles', 'work_start_time')) {
                $table->time('work_start_time')->nullable();
            }
            if (! Schema::hasColumn('staff_profiles', 'work_end_time')) {
                $table->time('work_end_time')->nullable();
            }
        });

        Schema::table('attendance', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance', 'schedule_flagged')) {
                $table->boolean('schedule_flagged')->default(false);
            }
            if (! Schema::hasColumn('attendance', 'schedule_flag_reason')) {
                $table->string('schedule_flag_reason', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['work_start_time', 'work_end_time']);
        });

        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropColumn(['work_start_time', 'work_end_time']);
        });

        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn(['schedule_flagged', 'schedule_flag_reason']);
        });
    }
};
