<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('attendance', function ($table) {
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete()->after('payroll_run_item_id');
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('attendance', function ($table) {
            $table->dropConstrainedForeignId('project_id');
        });
    }
};
