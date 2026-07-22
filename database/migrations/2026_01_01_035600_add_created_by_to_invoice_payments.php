<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        if (!$schema->hasColumn('invoice_payments', 'created_by')) {
            $schema->table('invoice_payments', function ($table) {
                $table->foreignId('created_by')->nullable()->constrained('staff_profiles')->nullOnDelete()->after('notes');
            });
        }
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        if ($schema->hasColumn('invoice_payments', 'created_by')) {
            $schema->table('invoice_payments', function ($table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
};
