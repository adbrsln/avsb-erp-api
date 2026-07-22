<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        // ── Attendance columns (migration 080/081 got out of sync) ──
        if (!$schema->hasColumn('attendance', 'clock_in_photo')) {
            $schema->table('attendance', function ($table) {
                $table->string('clock_in_photo', 255)->nullable();
            });
        }
        if (!$schema->hasColumn('attendance', 'clock_out_photo')) {
            $schema->table('attendance', function ($table) {
                $table->string('clock_out_photo', 255)->nullable();
            });
        }
        if (!$schema->hasColumn('attendance', 'flagged')) {
            $schema->table('attendance', function ($table) {
                $table->boolean('flagged')->default(false);
            });
        }
        if (!$schema->hasColumn('attendance', 'flagged_reason')) {
            $schema->table('attendance', function ($table) {
                $table->string('flagged_reason', 255)->nullable();
            });
        }
        if (!$schema->hasColumn('attendance', 'flagged_cleared_by')) {
            $schema->table('attendance', function ($table) {
                $table->foreignId('flagged_cleared_by')->nullable()->constrained('staff_profiles')->restrictOnDelete();
            });
        }
        if (!$schema->hasColumn('attendance', 'flagged_cleared_at')) {
            $schema->table('attendance', function ($table) {
                $table->datetime('flagged_cleared_at')->nullable();
            });
        }

        // ── Claims columns (migration 083) ──
        if (!$schema->hasColumn('claims', 'claim_ref')) {
            $schema->table('claims', function ($table) {
                $table->string('claim_ref', 50)->unique()->nullable();
            });
        }
        if (!$schema->hasColumn('claims', 'receipt_url')) {
            $schema->table('claims', function ($table) {
                $table->string('receipt_url')->nullable();
            });
        }
        if (!$schema->hasColumn('claims', 'rejection_reason')) {
            $schema->table('claims', function ($table) {
                $table->text('rejection_reason')->nullable();
            });
        }
        if (!$schema->hasColumn('claims', 'rejected_at')) {
            $schema->table('claims', function ($table) {
                $table->datetime('rejected_at')->nullable();
            });
        }
        if (!$schema->hasColumn('claims', 'paid_at')) {
            $schema->table('claims', function ($table) {
                $table->datetime('paid_at')->nullable();
            });
        }

        // Convert pending → submitted
        $schema->getConnection()->table('claims')
            ->where('status', 'pending')
            ->update(['status' => 'submitted']);
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        // No-op — too risky to drop columns that might have data
    }
};
