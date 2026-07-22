<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('project_documents', function ($table) {
            $table->foreignId('phase_id')->nullable()->constrained('phases')->nullOnDelete();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('project_documents', function ($table) {
            $table->dropForeign(['phase_id']);
            $table->dropColumn('phase_id');
        });
    }
};
