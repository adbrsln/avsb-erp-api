<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        Capsule::table('numbering_sequences')->insert([
            [
                'code' => 'self_billed_invoice',
                'prefix' => 'SBV-',
                'pattern' => '{PREFIX}{YEAR}-{MONTH}-{SEQ:4}',
                'last_sequence' => 0,
                'last_year_month' => '',
                'description' => 'Self-Billed Invoice',
            ],
            [
                'code' => 'subcontractor',
                'prefix' => 'SC-',
                'pattern' => '{PREFIX}{SEQ:4}',
                'last_sequence' => 0,
                'last_year_month' => '',
                'description' => 'Subcontractor',
            ],
            [
                'code' => 'subcontractor_claim',
                'prefix' => 'SCL-',
                'pattern' => '{PREFIX}{YEAR}-{MONTH}-{SEQ:4}',
                'last_sequence' => 0,
                'last_year_month' => '',
                'description' => 'Subcontractor Claim',
            ],
        ]);
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        Capsule::table('numbering_sequences')->whereIn('code', ['self_billed_invoice', 'subcontractor', 'subcontractor_claim'])->delete();
    }
};
