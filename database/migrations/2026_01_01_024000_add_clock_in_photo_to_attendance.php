<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('attendance', 'clock_in_photo')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->string('clock_in_photo', 255)->nullable();
            });
        }
        if (! Schema::hasColumn('attendance', 'clock_out_photo')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->string('clock_out_photo', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn(['clock_in_photo', 'clock_out_photo']);
        });
    }
};
