<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Attendance columns (migration 080/081 got out of sync) ──
        if (! Schema::hasColumn('attendance', 'clock_in_photo')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->string('clock_in_photo', 255)->nullable();
            });
        }
        if (! Schema::hasColumn('attendance', 'clock_out_photo')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->string('clock_out_photo', 255)->nullable();
            });
        }
        if (! Schema::hasColumn('attendance', 'flagged')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->boolean('flagged')->default(false);
            });
        }
        if (! Schema::hasColumn('attendance', 'flagged_reason')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->string('flagged_reason', 255)->nullable();
            });
        }
        if (! Schema::hasColumn('attendance', 'flagged_cleared_by')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->foreignId('flagged_cleared_by')->nullable()->constrained('staff_profiles')->restrictOnDelete();
            });
        }
        if (! Schema::hasColumn('attendance', 'flagged_cleared_at')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->datetime('flagged_cleared_at')->nullable();
            });
        }

        // ── Claims columns (migration 083) ──
        if (! Schema::hasColumn('claims', 'claim_ref')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->string('claim_ref', 50)->unique()->nullable();
            });
        }
        if (! Schema::hasColumn('claims', 'receipt_url')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->string('receipt_url')->nullable();
            });
        }
        if (! Schema::hasColumn('claims', 'rejection_reason')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->text('rejection_reason')->nullable();
            });
        }
        if (! Schema::hasColumn('claims', 'rejected_at')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->datetime('rejected_at')->nullable();
            });
        }
        if (! Schema::hasColumn('claims', 'paid_at')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->datetime('paid_at')->nullable();
            });
        }

        // Convert pending → submitted
        Schema::getConnection()->table('claims')
            ->where('status', 'pending')
            ->update(['status' => 'submitted']);
    }

    public function down(): void
    {
        // No-op — too risky to drop columns that might have data
    }
};
