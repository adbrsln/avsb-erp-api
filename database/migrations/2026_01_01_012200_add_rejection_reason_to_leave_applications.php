<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('leave_applications', function ($table) {
            $table->text('rejection_reason')->nullable()->after('reason');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('leave_applications', function ($table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
