<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->table('contracts', function ($table) {
            $table->json('items')->nullable()->after('billing_milestones');
        });

        $schema->table('invoices', function ($table) {
            $table->json('items')->nullable()->after('total');
        });
    }

    public function down(Builder $schema)
    {
        $schema->table('contracts', function ($table) {
            $table->dropColumn(['items']);
        });

        $schema->table('invoices', function ($table) {
            $table->dropColumn(['items']);
        });
    }
};
