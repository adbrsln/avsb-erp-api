<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('quotations', function ($table) {
            $table->decimal('sst_rate', 5, 2)->default(8.00)->after('sst');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('quotations', function ($table) {
            $table->dropColumn(['sst_rate']);
        });
    }
};
