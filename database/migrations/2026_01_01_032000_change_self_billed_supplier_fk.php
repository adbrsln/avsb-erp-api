<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('self_billed_invoices', function ($table) {
            $table->dropForeign(['supplier_id']);
            $table->foreign('supplier_id')->references('id')->on('subcontractors');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('self_billed_invoices', function ($table) {
            $table->dropForeign(['supplier_id']);
            $table->foreign('supplier_id')->references('id')->on('clients');
        });
    }
};
