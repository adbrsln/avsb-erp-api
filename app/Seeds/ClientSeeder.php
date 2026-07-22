<?php

namespace App\Seeds;

use App\Models\Client;
use App\Models\ClientPIC;

class ClientSeeder
{
    public function run(): void
    {
        $clients = [
            [
                'client_code' => 'CLT-TNB-001',
                'company_name' => 'Tenaga Nasional Berhad',
                'registration_no' => '200866-V',
                'phone' => '1-300-88-5454',
                'email' => 'procurement@tnb.com.my',
                'address' => 'No. 129, Jalan Bangsar, 59200 Kuala Lumpur',
                'billing_address' => 'Bahagian Perolehan, TNB, Aras 3, Menara TNB',
                'tax_id' => 'SST-TNB-001',
                'pics' => [
                    ['name' => 'Mohd Fadzly Bin Mohammad Tamsol', 'phone' => null, 'email' => 'fadzly.tamsol@tnb.com.my', 'job_title' => 'Project Engineer', 'department' => 'TNB Banting', 'is_primary' => false],
                    ['name' => 'Nur Jalilah Binti Abd Rahimi', 'phone' => null, 'email' => 'jalilah.rahimi@tnb.com.my', 'job_title' => 'Project Engineer', 'department' => 'TNB Klang', 'is_primary' => false],
                    ['name' => 'Nur Faogihah Binti Abdul Halim', 'phone' => null, 'email' => 'faogihah.halim@tnb.com.my', 'job_title' => 'Project Engineer', 'department' => 'TNB Klang', 'is_primary' => false],
                    ['name' => 'Mohd Shaiful Bin Mohd Nor', 'phone' => null, 'email' => 'shaiful.nor@tnb.com.my', 'job_title' => 'Project Engineer', 'department' => 'TNB Banting', 'is_primary' => false],
                    ['name' => 'Tarmizi', 'phone' => null, 'email' => 'tarmizi@tnb.com.my', 'job_title' => 'Project Engineer', 'department' => 'TNB Bangi', 'is_primary' => false],
                    ['name' => 'Khairil Izwan Bin Samsudin', 'phone' => null, 'email' => 'khairil.samsudin@tnb.com.my', 'job_title' => 'Senior Engineer', 'department' => 'TNB Petaling Jaya', 'is_primary' => true],
                    ['name' => 'Tuan Rosmadi', 'phone' => null, 'email' => 'rosmadi@tnb.com.my', 'job_title' => 'Project Manager', 'department' => 'TNB Shah Alam', 'is_primary' => true],
                    ['name' => 'Shahir Sify', 'phone' => null, 'email' => 'shahir.sify@tnb.com.my', 'job_title' => 'Project Engineer', 'department' => 'TNB Subang Jaya', 'is_primary' => false],
                    ['name' => 'Amir Rashidi', 'phone' => null, 'email' => 'amir.rashidi@tnb.com.my', 'job_title' => 'Project Engineer', 'department' => 'TNB Pelabuhan Klang', 'is_primary' => false],
                ],
            ],
        ];

        foreach ($clients as $c) {
            $pics = $c['pics'] ?? [];
            unset($c['pics']);
            $client = Client::create($c);
            foreach ($pics as $pic) {
                ClientPIC::create(array_merge($pic, ['client_id' => $client->id]));
            }
        }
    }
}
