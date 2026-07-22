<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('company_settings', function ($table) use ($schema) {
            if (! $schema->hasColumn('company_settings', 'logo_path')) {
                $table->string('logo_path', 255)->nullable();
            }
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('company_settings', function ($table) {
            $table->dropColumn('logo_path');
        });
    }
};
