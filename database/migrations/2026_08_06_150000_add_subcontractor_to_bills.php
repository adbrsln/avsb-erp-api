<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->change();
            $table->foreignId('subcontractor_id')->nullable()->after('vendor_id')->constrained('subcontractors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcontractor_id');
            $table->foreignId('vendor_id')->nullable(false)->change();
        });
    }
};
