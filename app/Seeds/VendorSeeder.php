<?php

namespace App\Seeds;

use App\Models\Vendor;

class VendorSeeder
{
    public function run(): void
    {
        if (Vendor::count() > 0) {
            return;
        }

        $vendors = [
            [
                'vendor_code' => 'V-PAV-001', 'company_name' => 'Kumpulan Eurovia Sdn Bhd',
                'registration_no' => '199701012345', 'phone' => '03-5569 1000',
                'email' => 'procurement@eurovia.com.my', 'address' => 'Lot 12, Jalan Perindustrian 5, 43650 Bandar Baru Bangi, Selangor',
                'contact_person' => 'En. Azman', 'status' => 'active', 'payment_terms' => '30 days',
            ],
            [
                'vendor_code' => 'V-AGG-001', 'company_name' => 'Pulai Rock & Sand Sdn Bhd',
                'registration_no' => '199801023456', 'phone' => '07-521 2345',
                'email' => 'sales@pulairock.com.my', 'address' => 'Batu 12, Jalan Kota Tinggi, 81000 Kulai, Johor',
                'contact_person' => 'En. Rahim', 'status' => 'active', 'payment_terms' => '30 days',
            ],
            [
                'vendor_code' => 'V-BIT-001', 'company_name' => 'Shell Malaysia Trading Sdn Bhd',
                'registration_no' => '196801034567', 'phone' => '03-2710 7000',
                'email' => 'bitumen@shell.com.my', 'address' => 'Menara Shell, No. 211, Jalan Tun Sambanthan, 50470 Kuala Lumpur',
                'contact_person' => 'Cik Salina', 'status' => 'active', 'payment_terms' => '45 days',
            ],
            [
                'vendor_code' => 'V-EQP-001', 'company_name' => 'United Equipment Holdings Sdn Bhd',
                'registration_no' => '200001045678', 'phone' => '03-6251 2345',
                'email' => 'rental@ueh.com.my', 'address' => 'No. 22, Jalan Pekeliling 3, 51200 Kuala Lumpur',
                'contact_person' => 'En. Wong', 'status' => 'active', 'payment_terms' => '14 days',
            ],
            [
                'vendor_code' => 'V-SAF-001', 'company_name' => 'Safety One Sdn Bhd',
                'registration_no' => '201001056789', 'phone' => '03-5633 4567',
                'email' => 'order@safetyone.com.my', 'address' => 'No. 5, Jalan SS25/32, 47301 Petaling Jaya, Selangor',
                'contact_person' => 'Pn. Aisyah', 'status' => 'active', 'payment_terms' => '30 days',
            ],
            [
                'vendor_code' => 'V-FUL-001', 'company_name' => 'Petronas Dagangan Bhd',
                'registration_no' => '198201067890', 'phone' => '03-2333 8000',
                'email' => 'b2b@petronas.com.my', 'address' => 'Tower 1, Petronas Twin Towers, 50088 Kuala Lumpur',
                'contact_person' => 'En. Kamal', 'status' => 'active', 'payment_terms' => '30 days',
            ],
            [
                'vendor_code' => 'V-OFF-001', 'company_name' => 'Popular Stationery & Office Supplies',
                'registration_no' => '199501078901', 'phone' => '03-2142 3456',
                'email' => 'corporate@popular.com.my', 'address' => 'No. 8, Jalan Bukit Bintang, 55100 Kuala Lumpur',
                'contact_person' => 'Cik Mei Ling', 'status' => 'active', 'payment_terms' => '14 days',
            ],
            [
                'vendor_code' => 'V-IT-001', 'company_name' => 'Dagang Net Technologies Sdn Bhd',
                'registration_no' => '199801089012', 'phone' => '03-2727 7777',
                'email' => 'sales@dagangnet.com.my', 'address' => 'Level 15, Menara TM, Jalan Pantai Baharu, 50672 Kuala Lumpur',
                'contact_person' => 'En. Siva', 'status' => 'active', 'payment_terms' => '30 days',
            ],
            [
                'vendor_code' => 'V-TRN-001', 'company_name' => 'MJ Logistics & Transport Sdn Bhd',
                'registration_no' => '200501099012', 'phone' => '03-5192 5678',
                'email' => 'dispatch@mjlogistics.com.my', 'address' => 'No. 3, Jalan Industri 1, 48000 Rawang, Selangor',
                'contact_person' => 'En. Hairi', 'status' => 'active', 'payment_terms' => '30 days',
            ],
            [
                'vendor_code' => 'V-ASF-001', 'company_name' => 'Carthago Asphalt Mix Sdn Bhd',
                'registration_no' => '200301109012', 'phone' => '03-6151 6789',
                'email' => 'sales@carthagoasphalt.com.my', 'address' => 'Lot 7, Jalan Pelabur 2, 40400 Shah Alam, Selangor',
                'contact_person' => 'En. Jamil', 'status' => 'active', 'payment_terms' => '30 days',
            ],
            [
                'vendor_code' => 'V-CEM-001', 'company_name' => 'Lafarge Malaysia Sdn Bhd',
                'registration_no' => '197501119012', 'phone' => '03-5569 3000',
                'email' => 'orders@lafarge.com.my', 'address' => 'Level 22, Wisma HLA, Jalan Raja Chulan, 50200 Kuala Lumpur',
                'contact_person' => 'En. Hassan', 'status' => 'active', 'payment_terms' => '30 days',
            ],
            [
                'vendor_code' => 'V-CON-001', 'company_name' => 'Concrete Solutions Sdn Bhd',
                'registration_no' => '200601129012', 'phone' => '03-5191 2345',
                'email' => 'sales@concretesol.com.my', 'address' => 'Lot 10, Jalan Industri 2, 48000 Rawang, Selangor',
                'contact_person' => 'En. Lim', 'status' => 'active', 'payment_terms' => '30 days',
            ],
            [
                'vendor_code' => 'V-PMT-001', 'company_name' => 'Permanent Mark Sdn Bhd',
                'registration_no' => '200701139012', 'phone' => '03-5541 4567',
                'email' => 'enquiry@permanentmark.com.my', 'address' => 'No. 15, Jalan Seri Utara 1, 68100 Batu Caves, Selangor',
                'contact_person' => 'Pn. Sheila', 'status' => 'active', 'payment_terms' => '30 days',
            ],
            [
                'vendor_code' => 'V-LUB-001', 'company_name' => 'Hock Lee Lubricants Sdn Bhd',
                'registration_no' => '201101149012', 'phone' => '03-6258 6789',
                'email' => 'sales@hocklee.com.my', 'address' => 'No. 28, Jalan Kuchai Lama, 58200 Kuala Lumpur',
                'contact_person' => 'En. Teck Hock', 'status' => 'active', 'payment_terms' => '14 days',
            ],
            [
                'vendor_code' => 'V-SUB-001', 'company_name' => 'Alam Sekitar Maju Sdn Bhd',
                'registration_no' => '200901159012', 'phone' => '03-5532 1234',
                'email' => 'admin@asmsb.com.my', 'address' => 'No. 2, Jalan Perusahaan 3, 47300 Petaling Jaya, Selangor',
                'contact_person' => 'En. Faizal', 'status' => 'active', 'payment_terms' => '45 days',
            ],
        ];

        foreach ($vendors as $v) {
            Vendor::firstOrCreate(['vendor_code' => $v['vendor_code']], $v);
        }
    }
}
