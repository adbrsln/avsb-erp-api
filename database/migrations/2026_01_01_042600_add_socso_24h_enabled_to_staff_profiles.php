<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('staff_profiles', function ($table) {
            $table->boolean('socso_24h_enabled')->default(false)->after('socso_contribution_type');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('staff_profiles', function ($table) {
            $table->dropColumn('socso_24h_enabled');
        });
    }
};
