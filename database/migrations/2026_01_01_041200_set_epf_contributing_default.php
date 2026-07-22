<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('staff_profiles', function ($table) {
            $table->boolean('epf_contributing')->default(true)->change();
        });

        // Fix existing active staff who have NULL/false epf_contributing
        $schema->getConnection()->table('staff_profiles')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('epf_contributing')->orWhere('epf_contributing', false);
            })
            ->update(['epf_contributing' => true]);
    }

    public function down(Builder $schema): void
    {
        $schema->table('staff_profiles', function ($table) {
            $table->boolean('epf_contributing')->nullable()->change();
        });
    }
};
