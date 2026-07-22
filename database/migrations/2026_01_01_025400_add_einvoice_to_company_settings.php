<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('company_settings', function ($table) {
            $table->string('sst_registration_no', 20)->nullable();
            $table->string('tax_id_number', 20)->nullable();
            $table->string('msic_code', 10)->nullable();
            $table->string('msic_description', 200)->nullable();
            $table->string('business_phone', 20)->nullable();
            $table->string('business_email', 100)->nullable();
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('company_settings', function ($table) {
            $table->dropColumn(['sst_registration_no', 'tax_id_number', 'msic_code', 'msic_description', 'business_phone', 'business_email']);
        });
    }
};
