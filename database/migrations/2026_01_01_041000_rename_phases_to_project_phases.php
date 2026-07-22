<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        if ($schema->hasTable('phases')) {
            $schema->rename('phases', 'project_phases');
        }
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        if ($schema->hasTable('project_phases')) {
            $schema->rename('project_phases', 'phases');
        }
    }
};
