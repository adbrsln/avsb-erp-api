<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->boolean('socso_contributing')->default(true)->after('socso_contribution_type');
        });

        // Fix existing active staff who have NULL socso_contributing (default on)
        Schema::getConnection()->table('staff_profiles')
            ->where('is_active', true)
            ->whereNull('socso_contributing')
            ->update(['socso_contributing' => true]);
    }

    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropColumn('socso_contributing');
        });
    }
};
