<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->table('leave_applications', function ($table) {
            $table->text('rejection_reason')->nullable()->after('reason');
        });
    }

    public function down(Builder $schema)
    {
        $schema->table('leave_applications', function ($table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
