<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('attendance', function ($table) {
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete()->after('payroll_run_item_id');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('attendance', function ($table) {
            $table->dropConstrainedForeignId('project_id');
        });
    }
};
