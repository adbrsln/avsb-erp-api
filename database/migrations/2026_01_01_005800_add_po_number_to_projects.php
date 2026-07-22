<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->table('projects', function ($table) {
            $table->string('po_number', 100)->nullable()->after('project_code');
            $table->string('project_code', 50)->nullable()->change();
        });
    }

    public function down(Builder $schema)
    {
        $schema->table('projects', function ($table) {
            $table->dropColumn('po_number');
        });
    }
};
