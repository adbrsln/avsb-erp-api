<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('tasks', function ($table) {
            $table->foreignId('started_by')->nullable()->constrained('staff_profiles')->nullOnDelete()->after('actual_start');
            $table->foreignId('paused_by')->nullable()->constrained('staff_profiles')->nullOnDelete()->after('paused_at');
            $table->foreignId('completed_by')->nullable()->constrained('staff_profiles')->nullOnDelete()->after('actual_end');
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('tasks', function ($table) {
            $table->dropForeign(['started_by']);
            $table->dropForeign(['paused_by']);
            $table->dropForeign(['completed_by']);
            $table->dropColumn(['started_by', 'paused_by', 'completed_by']);
        });
    }
};
