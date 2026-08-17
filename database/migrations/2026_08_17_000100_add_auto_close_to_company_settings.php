<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->boolean('auto_clock_out_enabled')->default(false)->after('geofence_enforced');
            $table->unsignedInteger('auto_clock_out_grace_minutes')->default(60)->after('auto_clock_out_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['auto_clock_out_enabled', 'auto_clock_out_grace_minutes']);
        });
    }
};
