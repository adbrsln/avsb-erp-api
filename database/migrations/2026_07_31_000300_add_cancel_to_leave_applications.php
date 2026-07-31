<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_applications', 'cancelled_at')) {
                $table->datetime('cancelled_at')->nullable();
            }
            if (! Schema::hasColumn('leave_applications', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable();
            }
            $table->foreign('cancelled_by')->references('id')->on('staff_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_at', 'cancelled_by']);
        });
    }
};
