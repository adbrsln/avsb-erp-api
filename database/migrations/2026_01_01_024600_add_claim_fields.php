<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        if (! $schema->hasColumn('claims', 'claim_ref')) {
            $schema->table('claims', function ($table) {
                $table->string('claim_ref', 50)->unique()->nullable()->after('id');
            });
        }
        if (! $schema->hasColumn('claims', 'receipt_url')) {
            $schema->table('claims', function ($table) {
                $table->string('receipt_url')->nullable()->after('items');
            });
        }
        if (! $schema->hasColumn('claims', 'rejection_reason')) {
            $schema->table('claims', function ($table) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            });
        }
        if (! $schema->hasColumn('claims', 'rejected_at')) {
            $schema->table('claims', function ($table) {
                $table->datetime('rejected_at')->nullable()->after('rejection_reason');
            });
        }
        if (! $schema->hasColumn('claims', 'paid_at')) {
            $schema->table('claims', function ($table) {
                $table->datetime('paid_at')->nullable()->after('rejected_at');
            });
        }

        // Convert old pending claims to new submitted status
        $schema->getConnection()->table('claims')
            ->where('status', 'pending')
            ->update(['status' => 'submitted']);
    }

    public function down(Builder $schema)
    {
        $schema->table('claims', function ($table) {
            $table->dropColumn(['claim_ref', 'receipt_url', 'rejection_reason', 'rejected_at', 'paid_at']);
        });
    }
};
