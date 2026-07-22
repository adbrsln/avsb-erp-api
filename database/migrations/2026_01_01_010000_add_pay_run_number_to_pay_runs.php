<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pay_runs', function (Blueprint $table) {
            $table->string('pay_run_number', 50)->nullable()->unique()->after('staff_id');
        });
    }

    public function down(): void
    {
        Schema::table('pay_runs', function (Blueprint $table) {
            $table->dropColumn('pay_run_number');
        });
    }
};
