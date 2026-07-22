<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->boolean('confirmed')->default(false)->after('paid_at');
            $table->datetime('confirmed_at')->nullable()->after('confirmed');
            $table->foreignId('confirmed_by')->nullable()->constrained('staff_profiles')->restrictOnDelete()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn(['confirmed', 'confirmed_at']);
        });
    }
};
