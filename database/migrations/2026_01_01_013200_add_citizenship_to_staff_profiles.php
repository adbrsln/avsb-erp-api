<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('staff_profiles', function ($table) {
            $table->string('citizenship', 20)->nullable()->after('nationality')
                ->comment('citizen|pr|non_citizen');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('staff_profiles', function ($table) {
            $table->dropColumn('citizenship');
        });
    }
};
