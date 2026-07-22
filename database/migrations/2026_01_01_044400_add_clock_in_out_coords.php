<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance', 'clock_in_latitude')) {
                $table->decimal('clock_in_latitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('attendance', 'clock_in_longitude')) {
                $table->decimal('clock_in_longitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('attendance', 'clock_out_latitude')) {
                $table->decimal('clock_out_latitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('attendance', 'clock_out_longitude')) {
                $table->decimal('clock_out_longitude', 10, 7)->nullable();
            }
        });

        // Migrate existing latitude/longitude to clock_in_latitude/clock_in_longitude
        Schema::getConnection()->statement(
            'UPDATE attendance SET clock_in_latitude = latitude, clock_in_longitude = longitude WHERE clock_in_latitude IS NULL AND latitude IS NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn(['clock_in_latitude', 'clock_in_longitude', 'clock_out_latitude', 'clock_out_longitude']);
        });
    }
};
