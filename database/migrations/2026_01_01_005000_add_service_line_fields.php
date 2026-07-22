<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->table('project_service_lines', function ($table) {
            $table->string('status', 20)->default('pending')->after('total');
            $table->date('planned_start')->nullable()->after('status');
            $table->date('planned_end')->nullable()->after('planned_start');
            $table->date('actual_start')->nullable()->after('planned_end');
            $table->date('actual_end')->nullable()->after('actual_start');
        });
    }

    public function down(Builder $schema)
    {
        $schema->table('project_service_lines', function ($table) {
            $table->dropColumn(['status', 'planned_start', 'planned_end', 'actual_start', 'actual_end']);
        });
    }
};
