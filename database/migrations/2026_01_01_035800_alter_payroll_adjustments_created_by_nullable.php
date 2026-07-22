<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->change();
        });
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('staff_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
        });
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('staff_profiles')->restrictOnDelete();
        });
    }
};
