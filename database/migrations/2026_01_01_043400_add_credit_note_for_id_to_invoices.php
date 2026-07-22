<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('invoices', function ($table) {
            $table->foreignId('credit_note_for_id')->nullable()->constrained('invoices')->nullOnDelete()->after('project_id');
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('invoices', function ($table) {
            $table->dropForeign(['credit_note_for_id']);
            $table->dropColumn('credit_note_for_id');
        });
    }
};
