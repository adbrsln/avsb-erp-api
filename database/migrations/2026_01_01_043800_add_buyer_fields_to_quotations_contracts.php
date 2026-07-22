<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['quotations', 'contracts'] as $table) {
            $apply = function (Blueprint $t) use ($table) {
                if (! Schema::hasColumn($table, 'buyer_tin')) {
                    $t->string('buyer_tin', 50)->nullable();
                }
                if (! Schema::hasColumn($table, 'buyer_reg_no')) {
                    $t->string('buyer_reg_no', 50)->nullable();
                }
                if (! Schema::hasColumn($table, 'buyer_sst_reg_no')) {
                    $t->string('buyer_sst_reg_no', 20)->nullable();
                }
                if (! Schema::hasColumn($table, 'buyer_contact')) {
                    $t->text('buyer_contact')->nullable();
                }
                if (! Schema::hasColumn($table, 'buyer_type')) {
                    $t->string('buyer_type', 20)->nullable();
                }
                if (! Schema::hasColumn($table, 'buyer_email')) {
                    $t->string('buyer_email', 255)->nullable();
                }
                if (! Schema::hasColumn($table, 'contact_phone')) {
                    $t->string('contact_phone', 50)->nullable();
                }
            };
            Schema::table($table, $apply);
        }
    }

    public function down(): void
    {
        foreach (['quotations', 'contracts'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['buyer_tin', 'buyer_reg_no', 'buyer_sst_reg_no', 'buyer_contact', 'buyer_type', 'buyer_email', 'contact_phone']);
            });
        }
    }
};
