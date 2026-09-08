<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->enum('statutory_type', ['wages', 'additional', 'overtime', 'reimbursement'])->nullable()->after('type');
        });

        // Existing earnings adjustments were treated as bonus/additional
        // remuneration — preserve that behaviour. Deductions carry no type.
        DB::table('payroll_adjustments')
            ->where('type', 'earnings')
            ->update(['statutory_type' => 'additional']);
    }

    public function down(): void
    {
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->dropColumn('statutory_type');
        });
    }
};
