<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->create('tax_codes', function ($table) {
            $table->id();
            $table->string('code', 5)->unique();
            $table->string('name', 100);
            $table->decimal('rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $codes = [
            ['code' => '01', 'name' => 'Sales Tax', 'rate' => 10.00],
            ['code' => '02', 'name' => 'Sales Tax — Reduced Rate', 'rate' => 5.00],
            ['code' => '03', 'name' => 'Sales Tax — Special Rate', 'rate' => 0.00],
            ['code' => '04', 'name' => 'Sales Tax — Specific Rate', 'rate' => 0.00],
            ['code' => '05', 'name' => 'Sales Tax — Exemption', 'rate' => 0.00],
            ['code' => '06', 'name' => 'Service Tax', 'rate' => 8.00],
            ['code' => '07', 'name' => 'Service Tax — Reduced Rate', 'rate' => 6.00],
            ['code' => '08', 'name' => 'Service Tax — Exemption', 'rate' => 0.00],
            ['code' => '09', 'name' => 'Tourism Tax', 'rate' => 0.00],
            ['code' => '10', 'name' => 'Departure Levy', 'rate' => 0.00],
            ['code' => '11', 'name' => 'Not Applicable', 'rate' => 0.00],
            ['code' => '12', 'name' => 'Out of Scope', 'rate' => 0.00],
            ['code' => 'AJP', 'name' => 'Exempt (relief/zero-rated)', 'rate' => 0.00],
        ];
        Capsule::table('tax_codes')->insert($codes);
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->dropIfExists('tax_codes');
    }
};
