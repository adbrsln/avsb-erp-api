<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('company_settings', function ($table) {
            $table->integer('socso_24h_phase')->default(1)->after('eis_no');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('company_settings', function ($table) {
            $table->dropColumn('socso_24h_phase');
        });
    }
};
