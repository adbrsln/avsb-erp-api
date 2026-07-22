<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        if (! $schema->hasColumn('assets', 'asset_code')) {
            $schema->table('assets', function ($table) {
                $table->string('asset_code', 50)->nullable()->unique()->after('name');
            });
        }
    }

    public function down(Builder $schema): void
    {
        if ($schema->hasColumn('assets', 'asset_code')) {
            $schema->table('assets', function ($table) {
                $table->dropColumn('asset_code');
            });
        }
    }
};
