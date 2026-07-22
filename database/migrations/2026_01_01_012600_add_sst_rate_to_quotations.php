<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->table('quotations', function ($table) {
            $table->decimal('sst_rate', 5, 2)->default(8.00)->after('sst');
        });
    }

    public function down(Builder $schema)
    {
        $schema->table('quotations', function ($table) {
            $table->dropColumn(['sst_rate']);
        });
    }
};
