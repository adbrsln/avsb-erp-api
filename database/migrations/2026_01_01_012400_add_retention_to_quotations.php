<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->decimal('retention_pct', 5, 2)->default(0)->after('sst');
            $table->decimal('retention_amount', 12, 2)->default(0)->after('retention_pct');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['retention_pct', 'retention_amount']);
        });
    }
};
