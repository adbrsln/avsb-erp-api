<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('activity_log', function ($table) {
            $table->index('created_at');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('activity_log', function ($table) {
            $table->dropIndex(['created_at']);
        });
    }
};
