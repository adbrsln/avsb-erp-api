<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('subcontractors', function ($table) {
            $table->string('sst_reg_no', 50)->nullable()->after('tax_id');
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('subcontractors', function ($table) {
            $table->dropColumn('sst_reg_no');
        });
    }
};
