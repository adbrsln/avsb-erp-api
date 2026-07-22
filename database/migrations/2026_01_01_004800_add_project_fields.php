<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->table('projects', function ($table) {
            $table->string('project_code', 50)->nullable()->after('name');
            $table->decimal('budget_amount', 12, 2)->nullable()->after('status');
            $table->foreignId('project_manager_id')->nullable()->constrained('staff_profiles')->nullOnDelete()->after('budget_amount');
        });
    }

    public function down(Builder $schema)
    {
        $schema->table('projects', function ($table) {
            $table->dropForeign(['project_manager_id']);
            $table->dropColumn(['project_code', 'budget_amount', 'project_manager_id']);
        });
    }
};
