<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('staff_profiles', function ($table) {
            $table->string('socso_category', 10)->default('first')->after('socso_24h_enabled');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('staff_profiles', function ($table) {
            $table->dropColumn('socso_category');
        });
    }
};
