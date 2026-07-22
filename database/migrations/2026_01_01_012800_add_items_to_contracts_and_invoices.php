<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('contracts', function ($table) {
            $table->json('items')->nullable()->after('billing_milestones');
        });

        $schema->table('invoices', function ($table) {
            $table->json('items')->nullable()->after('total');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('contracts', function ($table) {
            $table->dropColumn(['items']);
        });

        $schema->table('invoices', function ($table) {
            $table->dropColumn(['items']);
        });
    }
};
