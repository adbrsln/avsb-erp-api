<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        if (!$schema->hasColumn('assets', 'asset_code')) {
            $schema->table('assets', function ($table) {
                $table->string('asset_code', 50)->nullable()->unique()->after('name');
            });
        }
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        if ($schema->hasColumn('assets', 'asset_code')) {
            $schema->table('assets', function ($table) {
                $table->dropColumn('asset_code');
            });
        }
    }
};
