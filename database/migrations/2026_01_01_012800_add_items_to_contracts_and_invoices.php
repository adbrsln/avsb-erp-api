<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->json('items')->nullable()->after('billing_milestones');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->json('items')->nullable()->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['items']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['items']);
        });
    }
};
