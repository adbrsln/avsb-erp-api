<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->boolean('epf_contributing')->default(true)->change();
        });

        // Fix existing active staff who have NULL/false epf_contributing
        Schema::getConnection()->table('staff_profiles')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('epf_contributing')->orWhere('epf_contributing', false);
            })
            ->update(['epf_contributing' => true]);
    }

    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->boolean('epf_contributing')->nullable()->change();
        });
    }
};
