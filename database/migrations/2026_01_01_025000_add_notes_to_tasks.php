<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('tasks', function ($table) {
            $table->text('pause_notes')->nullable()->after('pause_reason');
            $table->text('completion_notes')->nullable()->after('paused_at');
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('tasks', function ($table) {
            $table->dropColumn(['pause_notes', 'completion_notes']);
        });
    }
};
