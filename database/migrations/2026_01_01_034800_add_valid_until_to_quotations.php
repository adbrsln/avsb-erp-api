<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('quotations', 'valid_until')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->date('valid_until')->nullable()->after('date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('quotations', 'valid_until')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->dropColumn('valid_until');
            });
        }
    }
};
