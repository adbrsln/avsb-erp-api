<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('staff_profiles', function ($table) {
            $table->boolean('socso_24h_enabled')->default(false)->after('socso_contribution_type');
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('staff_profiles', function ($table) {
            $table->dropColumn('socso_24h_enabled');
        });
    }
};
