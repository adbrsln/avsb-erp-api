<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('subcontractors', function ($table) {
            $table->string('sst_reg_no', 50)->nullable()->after('tax_id');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('subcontractors', function ($table) {
            $table->dropColumn('sst_reg_no');
        });
    }
};
