<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('project_documents', function ($table) {
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('project_documents', function ($table) {
            $table->dropForeign(['task_id']);
            $table->dropColumn('task_id');
        });
    }
};
