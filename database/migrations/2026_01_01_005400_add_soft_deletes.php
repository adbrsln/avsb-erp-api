<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('quotations', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('contracts', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('claims', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('timecards', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('pay_runs', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('claims', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('timecards', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('pay_runs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
