<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->boolean('eis_contributing')->default(true)->change();
        });

        // Fix existing active staff who have NULL eis_contributing (default on)
        Schema::getConnection()->table('staff_profiles')
            ->where('is_active', true)
            ->whereNull('eis_contributing')
            ->update(['eis_contributing' => true]);
    }

    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->boolean('eis_contributing')->nullable()->change();
        });
    }
};
