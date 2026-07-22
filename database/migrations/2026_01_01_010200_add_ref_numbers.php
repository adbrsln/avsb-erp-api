<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('work_orders', function ($table) {
            $table->string('work_order_number', 50)->nullable()->unique()->after('id');
        });
        $schema->table('project_service_lines', function ($table) {
            $table->string('service_line_ref', 50)->nullable()->unique()->after('id');
        });
        $schema->table('leave_applications', function ($table) {
            $table->string('leave_ref', 50)->nullable()->unique()->after('id');
        });
        $schema->table('claims', function ($table) {
            $table->string('claim_ref', 50)->nullable()->unique()->after('id');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('work_orders', function ($table) {
            $table->dropColumn('work_order_number');
        });
        $schema->table('project_service_lines', function ($table) {
            $table->dropColumn('service_line_ref');
        });
        $schema->table('leave_applications', function ($table) {
            $table->dropColumn('leave_ref');
        });
        $schema->table('claims', function ($table) {
            $table->dropColumn('claim_ref');
        });
    }
};
