<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('sst_registration_no', 20)->nullable();
            $table->string('tax_id_number', 20)->nullable();
            $table->string('msic_code', 10)->nullable();
            $table->string('msic_description', 200)->nullable();
            $table->string('business_phone', 20)->nullable();
            $table->string('business_email', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['sst_registration_no', 'tax_id_number', 'msic_code', 'msic_description', 'business_phone', 'business_email']);
        });
    }
};
