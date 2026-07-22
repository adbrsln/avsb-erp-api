<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('claims', 'claim_ref')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->string('claim_ref', 50)->unique()->nullable()->after('id');
            });
        }
        if (! Schema::hasColumn('claims', 'receipt_url')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->string('receipt_url')->nullable()->after('items');
            });
        }
        if (! Schema::hasColumn('claims', 'rejection_reason')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            });
        }
        if (! Schema::hasColumn('claims', 'rejected_at')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->datetime('rejected_at')->nullable()->after('rejection_reason');
            });
        }
        if (! Schema::hasColumn('claims', 'paid_at')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->datetime('paid_at')->nullable()->after('rejected_at');
            });
        }

        // Convert old pending claims to new submitted status
        Schema::getConnection()->table('claims')
            ->where('status', 'pending')
            ->update(['status' => 'submitted']);
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn(['claim_ref', 'receipt_url', 'rejection_reason', 'rejected_at', 'paid_at']);
        });
    }
};
