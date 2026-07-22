<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->table('project_documents', function ($table) {
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
        });
    }

    public function down(Builder $schema)
    {
        $schema->table('project_documents', function ($table) {
            $table->dropForeign(['task_id']);
            $table->dropColumn('task_id');
        });
    }
};
