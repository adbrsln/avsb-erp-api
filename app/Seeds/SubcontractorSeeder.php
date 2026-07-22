<?php

namespace App\Seeds;

use App\Models\Subcontractor;

class SubcontractorSeeder
{
    public function run(): void
    {
        $subs = [
            [
                'subcontractor_code' => 'SUB-IMW-001',
                'company_name' => 'IMW Sdn Bhd',
                'registration_no' => 'SSM-IMW-2020',
                'phone' => '03-1234 5678',
                'email' => 'admin@imw.com.my',
                'address' => 'No. 15, Jalan Industri 3/1, 43650 Bandar Baru Bangi, Selangor',
                'contact_person' => 'En. Ahmad',
                'contact_phone' => '012-345 6789',
                'status' => 'active',
            ],
            [
                'subcontractor_code' => 'SUB-JMW-001',
                'company_name' => 'JMW Sdn Bhd',
                'registration_no' => 'SSM-JMW-2019',
                'phone' => '03-2345 6789',
                'email' => 'admin@jmw.com.my',
                'address' => 'No. 45, Jalan Pelabur 2/1, 40400 Shah Alam, Selangor',
                'contact_person' => 'En. Rosli',
                'contact_phone' => '013-456 7890',
                'status' => 'active',
            ],
            [
                'subcontractor_code' => 'SUB-SOL-001',
                'company_name' => 'SOLARA',
                'registration_no' => 'SSM-SOL-2021',
                'phone' => '03-3456 7890',
                'email' => 'admin@solara.com.my',
                'address' => 'No. 88, Jalan Industri 5/2, 43650 Bandar Baru Bangi, Selangor',
                'contact_person' => 'En. Rahim',
                'contact_phone' => '014-567 8901',
                'status' => 'active',
            ],
            [
                'subcontractor_code' => 'SUB-OAS-001',
                'company_name' => 'Omar Air Selangor',
                'registration_no' => 'SSM-OAS-2018',
                'phone' => '03-4567 8901',
                'email' => 'admin@omarairselangor.com.my',
                'address' => 'No. 12, Jalan Lapangan 1, 40000 Shah Alam, Selangor',
                'contact_person' => 'En. Omar',
                'contact_phone' => '016-789 0123',
                'status' => 'active',
            ],
            [
                'subcontractor_code' => 'SUB-SSV-001',
                'company_name' => 'Saiful S&A Ventures',
                'registration_no' => 'SSM-SSV-2022',
                'phone' => '03-5678 9012',
                'email' => 'admin@saiful-sa.com.my',
                'address' => 'No. 5, Jalan Niaga 3/1, 43000 Kajang, Selangor',
                'contact_person' => 'En. Saiful',
                'contact_phone' => '017-890 1234',
                'status' => 'active',
            ],
        ];

        foreach ($subs as $s) {
            Subcontractor::firstOrCreate(
                ['subcontractor_code' => $s['subcontractor_code']],
                $s
            );
        }
    }
}
