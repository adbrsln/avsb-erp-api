<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        if (!$schema->hasColumn('leave_applications', 'is_half_day')) {
            $schema->table('leave_applications', function ($table) {
                $table->boolean('is_half_day')->default(false)->after('end_date');
            });
        }
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        if ($schema->hasColumn('leave_applications', 'is_half_day')) {
            $schema->table('leave_applications', function ($table) {
                $table->dropColumn('is_half_day');
            });
        }
    }
};
