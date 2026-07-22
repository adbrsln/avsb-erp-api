<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assets', 'asset_code')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->string('asset_code', 50)->nullable()->unique()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('assets', 'asset_code')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->dropColumn('asset_code');
            });
        }
    }
};
