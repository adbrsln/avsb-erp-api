<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->table('invoices', function ($table) {
            $table->string('payment_reference')->nullable()->after('processed_at');
        });
    }

    public function down(Builder $schema)
    {
        $schema->table('invoices', function ($table) {
            $table->dropColumn('payment_reference');
        });
    }
};
