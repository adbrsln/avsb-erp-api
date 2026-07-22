<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('company_settings', function ($table) use ($schema) {
            if (!$schema->hasColumn('company_settings', 'logo_path')) {
                $table->string('logo_path', 255)->nullable();
            }
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('company_settings', function ($table) {
            $table->dropColumn('logo_path');
        });
    }
};
