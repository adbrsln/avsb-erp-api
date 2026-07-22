<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        if (!$schema->hasColumn('quotations', 'valid_until')) {
            $schema->table('quotations', function ($table) {
                $table->date('valid_until')->nullable()->after('date');
            });
        }
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        if ($schema->hasColumn('quotations', 'valid_until')) {
            $schema->table('quotations', function ($table) {
                $table->dropColumn('valid_until');
            });
        }
    }
};
