<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('geofences')) {
            Schema::create('geofences', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('description')->nullable();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->unsignedInteger('radius_meters')->default(100);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('staff_profiles')->nullOnDelete();
                $table->index('is_active');
            });
        }

        Schema::table('attendance', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance', 'geofence_id')) {
                $table->unsignedBigInteger('geofence_id')->nullable();
            }
            if (! Schema::hasColumn('attendance', 'clock_out_geofence_id')) {
                $table->unsignedBigInteger('clock_out_geofence_id')->nullable();
            }
            $table->foreign('geofence_id')->references('id')->on('geofences')->nullOnDelete();
            $table->foreign('clock_out_geofence_id')->references('id')->on('geofences')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropForeign(['geofence_id']);
            $table->dropForeign(['clock_out_geofence_id']);
            $table->dropColumn(['geofence_id', 'clock_out_geofence_id']);
        });

        Schema::dropIfExists('geofences');
    }
};
