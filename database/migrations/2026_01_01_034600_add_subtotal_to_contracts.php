<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('contracts', 'subtotal')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->decimal('subtotal', 12, 2)->default(0)->after('total_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contracts', 'subtotal')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropColumn('subtotal');
            });
        }
    }
};
