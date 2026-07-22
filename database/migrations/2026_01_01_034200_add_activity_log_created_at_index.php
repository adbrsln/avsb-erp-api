<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->table('activity_log', function ($table) {
            $table->index('created_at');
        });
    }

    public function down(Builder $schema)
    {
        $schema->table('activity_log', function ($table) {
            $table->dropIndex(['created_at']);
        });
    }
};
