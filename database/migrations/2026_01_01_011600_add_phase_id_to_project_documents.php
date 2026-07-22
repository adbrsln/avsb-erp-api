<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->table('project_documents', function ($table) {
            $table->foreignId('phase_id')->nullable()->constrained('phases')->nullOnDelete();
        });
    }

    public function down(Builder $schema)
    {
        $schema->table('project_documents', function ($table) {
            $table->dropForeign(['phase_id']);
            $table->dropColumn('phase_id');
        });
    }
};
