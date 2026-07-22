<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->create('company_settings', function ($table) {
            $table->id();
            $table->string('company_name');
            $table->text('address')->nullable();
            $table->string('reg_no', 50)->nullable();
            $table->string('epf_no', 50)->nullable();
            $table->string('socso_no', 50)->nullable();
            $table->string('eis_no', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(Builder $schema)
    {
        $schema->dropIfExists('company_settings');
    }
};
