<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('source')->default('system')->after('status');
            $table->string('legacy_document_path')->nullable()->after('items');
            $table->string('legacy_document_filename')->nullable()->after('legacy_document_path');
            $table->date('legacy_paid_date')->nullable()->after('legacy_document_filename');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['source', 'legacy_document_path', 'legacy_document_filename', 'legacy_paid_date']);
        });
    }
};
