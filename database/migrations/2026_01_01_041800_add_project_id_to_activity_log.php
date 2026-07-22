<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('activity_log', function ($table) {
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete()->after('id');
            $table->index('project_id');
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('activity_log', function ($table) {
            $table->dropIndex(['project_id']);
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
