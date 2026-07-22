<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('work_order_number', 50)->nullable()->unique()->after('id');
        });
        Schema::table('project_service_lines', function (Blueprint $table) {
            $table->string('service_line_ref', 50)->nullable()->unique()->after('id');
        });
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->string('leave_ref', 50)->nullable()->unique()->after('id');
        });
        Schema::table('claims', function (Blueprint $table) {
            $table->string('claim_ref', 50)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn('work_order_number');
        });
        Schema::table('project_service_lines', function (Blueprint $table) {
            $table->dropColumn('service_line_ref');
        });
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropColumn('leave_ref');
        });
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn('claim_ref');
        });
    }
};
