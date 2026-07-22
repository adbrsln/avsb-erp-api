<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        if (! $schema->hasColumn('quotations', 'valid_until')) {
            $schema->table('quotations', function ($table) {
                $table->date('valid_until')->nullable()->after('date');
            });
        }
    }

    public function down(Builder $schema): void
    {
        if ($schema->hasColumn('quotations', 'valid_until')) {
            $schema->table('quotations', function ($table) {
                $table->dropColumn('valid_until');
            });
        }
    }
};
