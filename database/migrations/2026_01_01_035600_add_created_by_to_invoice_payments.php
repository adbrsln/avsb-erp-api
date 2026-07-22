<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        if (! $schema->hasColumn('invoice_payments', 'created_by')) {
            $schema->table('invoice_payments', function ($table) {
                $table->foreignId('created_by')->nullable()->constrained('staff_profiles')->nullOnDelete()->after('notes');
            });
        }
    }

    public function down(Builder $schema): void
    {
        if ($schema->hasColumn('invoice_payments', 'created_by')) {
            $schema->table('invoice_payments', function ($table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
};
