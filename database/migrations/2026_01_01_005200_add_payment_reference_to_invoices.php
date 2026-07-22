<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('invoices', function ($table) {
            $table->string('payment_reference')->nullable()->after('processed_at');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('invoices', function ($table) {
            $table->dropColumn('payment_reference');
        });
    }
};
