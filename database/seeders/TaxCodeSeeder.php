<?php

namespace Database\Seeders;

use App\Models\TaxCode;

class TaxCodeSeeder
{
    public function run(): void
    {
        if (TaxCode::count() > 0) {
            return;
        }

        TaxCode::insert([
            ['code' => 'SR', 'name' => 'Standard Rate (8% SST)', 'rate' => 8.00, 'is_active' => true],
            ['code' => 'SR-6', 'name' => 'Standard Rate (6% SST)', 'rate' => 6.00, 'is_active' => true],
            ['code' => 'ZRL', 'name' => 'Zero Rated Local', 'rate' => 0.00, 'is_active' => true],
            ['code' => 'ZRS', 'name' => 'Zero Rated Services', 'rate' => 0.00, 'is_active' => true],
            ['code' => 'ES', 'name' => 'Exempt Supply', 'rate' => 0.00, 'is_active' => true],
            ['code' => 'OS', 'name' => 'Out of Scope', 'rate' => 0.00, 'is_active' => true],
            ['code' => 'AJS', 'name' => 'Adjustment', 'rate' => 0.00, 'is_active' => true],
            ['code' => 'TX-E', 'name' => 'Exempt (E-Invoice)', 'rate' => 0.00, 'is_active' => true],
            ['code' => 'TX-NR', 'name' => 'Not Rated (E-Invoice)', 'rate' => 0.00, 'is_active' => true],
            ['code' => '11', 'name' => 'Not Applicable', 'rate' => 0.00, 'is_active' => true],
        ]);
    }
}
