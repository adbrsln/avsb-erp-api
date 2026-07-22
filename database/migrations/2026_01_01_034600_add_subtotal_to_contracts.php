<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        if (! $schema->hasColumn('contracts', 'subtotal')) {
            $schema->table('contracts', function ($table) {
                $table->decimal('subtotal', 12, 2)->default(0)->after('total_amount');
            });
        }
    }

    public function down(Builder $schema): void
    {
        if ($schema->hasColumn('contracts', 'subtotal')) {
            $schema->table('contracts', function ($table) {
                $table->dropColumn('subtotal');
            });
        }
    }
};
