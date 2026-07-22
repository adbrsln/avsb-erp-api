<?php

namespace Database\Seeders;

use App\Models\CompanySetting;

class CompanySettingSeeder
{
    public function run(): void
    {
        if (CompanySetting::count() > 0) {
            echo "Skipped: Company settings already exist.\n";

            return;
        }

        CompanySetting::create([
            'company_name' => 'Azam Ventures Sdn. Bhd.',
            'reg_no' => '199301025479 (280217-W)',
            'address' => '2, 19A, 3, Jln. Wawasan Ampang, Bandar Baru Ampang, 68000 Ampang Jaya, Selangor',
            'business_phone' => '+603 4287 3867',
            'business_email' => 'azamvsb@gmail.com',
            'tax_id_number' => '5873702020',
            'sst_registration_no' => 'N/A',
            'epf_no' => '10615470',
            'socso_no' => 'A3700021581M',
            'eis_no' => 'A3700021581M',
            'msic_code' => '42909',
            'msic_description' => 'Construction of other engineering projects n.e.c.',
        ]);

        echo "Seeded company settings.\n";
    }
}
