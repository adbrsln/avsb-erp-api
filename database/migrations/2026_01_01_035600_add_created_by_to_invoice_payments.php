<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoice_payments', 'created_by')) {
            Schema::table('invoice_payments', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->constrained('staff_profiles')->nullOnDelete()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoice_payments', 'created_by')) {
            Schema::table('invoice_payments', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
};
