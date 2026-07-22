<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'sst_reg_no')) {
                $table->string('sst_reg_no', 20)->nullable();
            }
            if (! Schema::hasColumn('clients', 'buyer_type')) {
                $table->string('buyer_type', 20)->nullable();
            }
            if (! Schema::hasColumn('clients', 'contact_phone')) {
                $table->string('contact_phone', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['sst_reg_no', 'buyer_type', 'contact_phone']);
        });
    }
};
