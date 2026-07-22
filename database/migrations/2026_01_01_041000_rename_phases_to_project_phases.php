<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        if ($schema->hasTable('phases')) {
            $schema->rename('phases', 'project_phases');
        }
    }

    public function down(Builder $schema): void
    {
        if ($schema->hasTable('project_phases')) {
            $schema->rename('project_phases', 'phases');
        }
    }
};
