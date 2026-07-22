<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('clients', function ($table) use ($schema) {
            if (! $schema->hasColumn('clients', 'sst_reg_no')) {
                $table->string('sst_reg_no', 20)->nullable();
            }
            if (! $schema->hasColumn('clients', 'buyer_type')) {
                $table->string('buyer_type', 20)->nullable();
            }
            if (! $schema->hasColumn('clients', 'contact_phone')) {
                $table->string('contact_phone', 20)->nullable();
            }
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('clients', function ($table) {
            $table->dropColumn(['sst_reg_no', 'buyer_type', 'contact_phone']);
        });
    }
};
