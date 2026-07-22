<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('quotations', function ($table) {
            $table->decimal('retention_pct', 5, 2)->default(0)->after('sst');
            $table->decimal('retention_amount', 12, 2)->default(0)->after('retention_pct');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('quotations', function ($table) {
            $table->dropColumn(['retention_pct', 'retention_amount']);
        });
    }
};
