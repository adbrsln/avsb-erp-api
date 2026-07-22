<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        if (!$schema->hasColumn('attendance', 'clock_in_photo')) {
            $schema->table('attendance', function ($table) {
                $table->string('clock_in_photo', 255)->nullable();
            });
        }
        if (!$schema->hasColumn('attendance', 'clock_out_photo')) {
            $schema->table('attendance', function ($table) {
                $table->string('clock_out_photo', 255)->nullable();
            });
        }
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('attendance', function ($table) {
            $table->dropColumn(['clock_in_photo', 'clock_out_photo']);
        });
    }
};
