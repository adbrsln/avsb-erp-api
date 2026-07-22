<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('staff_profiles', function ($table) {
            $table->string('socso_category', 10)->default('first')->after('socso_24h_enabled');
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('staff_profiles', function ($table) {
            $table->dropColumn('socso_category');
        });
    }
};
