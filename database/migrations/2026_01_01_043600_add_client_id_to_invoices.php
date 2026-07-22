<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('client');
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            }
            if (! Schema::hasColumn('invoices', 'buyer_type')) {
                $table->string('buyer_type', 20)->nullable()->after('buyer_tin');
            }
            if (! Schema::hasColumn('invoices', 'buyer_email')) {
                $table->string('buyer_email', 255)->nullable()->after('buyer_contact');
            }
            if (! Schema::hasColumn('invoices', 'contact_phone')) {
                $table->string('contact_phone', 50)->nullable()->after('buyer_email');
            }
            if (! Schema::hasColumn('invoices', 'einvoice_status')) {
                $table->string('einvoice_status', 20)->nullable()->after('einvoice_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['client_id', 'buyer_type', 'buyer_email', 'contact_phone', 'einvoice_status']);
        });
    }
};
