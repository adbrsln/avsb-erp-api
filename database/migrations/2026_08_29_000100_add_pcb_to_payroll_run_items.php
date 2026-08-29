<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->decimal('pcb_employee', 10, 2)->default(0)->after('socso_24h_employee');
            $table->decimal('zakat', 10, 2)->default(0)->after('pcb_employee');
            $table->smallInteger('pcb_tax_year')->nullable()->after('zakat');
            $table->json('pcb_method')->nullable()->after('pcb_tax_year');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->dropColumn(['pcb_employee', 'zakat', 'pcb_tax_year', 'pcb_method']);
        });
    }
};
