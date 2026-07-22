<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('projects', function ($table) use ($schema) {
            if (! $schema->hasColumn('projects', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }
            if (! $schema->hasColumn('projects', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('projects', function ($table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
