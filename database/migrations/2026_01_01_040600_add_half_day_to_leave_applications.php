<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('leave_applications', 'is_half_day')) {
            Schema::table('leave_applications', function (Blueprint $table) {
                $table->boolean('is_half_day')->default(false)->after('end_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_applications', 'is_half_day')) {
            Schema::table('leave_applications', function (Blueprint $table) {
                $table->dropColumn('is_half_day');
            });
        }
    }
};
