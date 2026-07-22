<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('self_billed_invoices', function ($table) {
            $table->dropForeign(['supplier_id']);
            $table->foreign('supplier_id')->references('id')->on('subcontractors');
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('self_billed_invoices', function ($table) {
            $table->dropForeign(['supplier_id']);
            $table->foreign('supplier_id')->references('id')->on('clients');
        });
    }
};
