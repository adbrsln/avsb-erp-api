<?php

namespace Database\Seeders;

use App\Models\ServiceCatalogItem;

class ServiceCatalogSeeder
{
    public function run(): void
    {
        if (ServiceCatalogItem::count() > 0) {
            return;
        }

        $items = [
            // ── Paving ──
            ['name' => 'ACW14 Asphalt Laying', 'description' => 'Laying of ACW14 asphalt concrete wearing course including compaction to specified density and surface tolerance compliance.', 'unit' => 'Sqm', 'unit_rate' => 45.00, 'tax_code' => '11', 'category' => 'Paving'],
            ['name' => 'ACW10 Asphalt Laying', 'description' => 'Laying of ACW10 asphalt concrete wearing course with fine aggregate mix including compaction and joint construction.', 'unit' => 'Sqm', 'unit_rate' => 42.00, 'tax_code' => '11', 'category' => 'Paving'],
            ['name' => 'ACB Binder Course', 'description' => 'Laying of asphalt concrete binder course as base layer including compaction to achieve specified density.', 'unit' => 'Sqm', 'unit_rate' => 38.00, 'tax_code' => '11', 'category' => 'Paving'],
            ['name' => 'Tack Coat Spraying', 'description' => 'Application of bituminous tack coat to existing surface prior to asphalt overlay for interlayer bonding.', 'unit' => 'Sqm', 'unit_rate' => 3.50, 'tax_code' => '11', 'category' => 'Paving'],
            ['name' => 'Prime Coat', 'description' => 'Application of prime coat to prepared granular base prior to asphalt paving for dust proofing and bonding.', 'unit' => 'Sqm', 'unit_rate' => 4.00, 'tax_code' => '11', 'category' => 'Paving'],
            ['name' => 'Compaction - Initial Rolling', 'description' => 'Initial breakdown rolling of asphalt mat using steel wheel roller immediately after laying.', 'unit' => 'Sqm', 'unit_rate' => 2.50, 'tax_code' => '11', 'category' => 'Paving'],
            ['name' => 'Compaction - Intermediate Rolling', 'description' => 'Intermediate rolling of asphalt using pneumatic tyre roller to achieve intermediate density.', 'unit' => 'Sqm', 'unit_rate' => 2.00, 'tax_code' => '11', 'category' => 'Paving'],
            ['name' => 'Compaction - Final Rolling', 'description' => 'Final finish rolling of asphalt using steel wheel roller to achieve specified final density and surface finish.', 'unit' => 'Sqm', 'unit_rate' => 2.00, 'tax_code' => '11', 'category' => 'Paving'],
            ['name' => 'Asphalt Reinstatement', 'description' => 'Cutting out and reinstatement of existing asphalt surface including base preparation, laying, and compaction.', 'unit' => 'Sqm', 'unit_rate' => 55.00, 'tax_code' => '11', 'category' => 'Paving'],
            ['name' => 'Road Sweeping & Cleaning', 'description' => 'Mechanical sweeping and cleaning of road surface prior to paving works including debris removal.', 'unit' => 'Sqm', 'unit_rate' => 1.00, 'tax_code' => '11', 'category' => 'Paving'],
            ['name' => 'Milling Machine Mobilization', 'description' => 'Transportation, setup, and calibration of cold milling machine including operator and all necessary equipment.', 'unit' => 'Trip', 'unit_rate' => 2500.00, 'tax_code' => '11', 'category' => 'Paving'],
            ['name' => 'Surface Regularity Test', 'description' => 'Testing of finished road surface regularity using 3m straightedge to ensure compliance with specification.', 'unit' => 'Meter', 'unit_rate' => 1.50, 'tax_code' => '11', 'category' => 'Paving'],

            // ── Milling ──
            ['name' => 'Milling 40mm Depth', 'description' => 'Cold milling of existing road surface to 40mm depth including loading and removal of milled material.', 'unit' => 'Sqm', 'unit_rate' => 8.50, 'tax_code' => '11', 'category' => 'Milling'],
            ['name' => 'Milling 50mm Depth', 'description' => 'Cold milling of existing road surface to 50mm depth including loading, hauling, and disposal of milled material.', 'unit' => 'Sqm', 'unit_rate' => 10.00, 'tax_code' => '11', 'category' => 'Milling'],
            ['name' => 'Milling 75mm Depth', 'description' => 'Cold milling of existing road surface to 75mm depth including removal and disposal of milled asphalt.', 'unit' => 'Sqm', 'unit_rate' => 14.00, 'tax_code' => '11', 'category' => 'Milling'],
            ['name' => 'Milling 100mm Depth', 'description' => 'Cold milling of existing road surface to 100mm depth suitable for full-depth pavement removal.', 'unit' => 'Sqm', 'unit_rate' => 18.00, 'tax_code' => '11', 'category' => 'Milling'],
            ['name' => 'Cold Milling', 'description' => 'General cold milling of asphalt surface to specified depth using Wirtgen or equivalent milling machine.', 'unit' => 'Sqm', 'unit_rate' => 9.00, 'tax_code' => '11', 'category' => 'Milling'],
            ['name' => 'Road Planing', 'description' => 'Road planing to remove surface irregularities and restore proper crossfall and longitudinal profile.', 'unit' => 'Sqm', 'unit_rate' => 7.00, 'tax_code' => '11', 'category' => 'Milling'],
            ['name' => 'Debris Removal & Hauling', 'description' => 'Loading, hauling, and disposal of milled asphalt and construction debris to approved disposal site.', 'unit' => 'Trip', 'unit_rate' => 250.00, 'tax_code' => '11', 'category' => 'Milling'],
            ['name' => 'Milling Machine Hire', 'description' => 'Hire of cold milling machine with operator for road profiling and pavement removal works.', 'unit' => 'Day', 'unit_rate' => 3500.00, 'tax_code' => '11', 'category' => 'Milling'],
            ['name' => 'Vacuum Truck Service', 'description' => 'Vacuum truck service for cleaning of milled surface, joint preparation, and debris collection.', 'unit' => 'Hour', 'unit_rate' => 150.00, 'tax_code' => '11', 'category' => 'Milling'],

            // ── Road Marking ──
            ['name' => 'Thermoplastic Line 100mm', 'description' => 'Application of reflectorized thermoplastic road marking of 100mm width including glass bead embedding.', 'unit' => 'Meter', 'unit_rate' => 4.50, 'tax_code' => '11', 'category' => 'Road Marking'],
            ['name' => 'Thermoplastic Line 150mm', 'description' => 'Application of reflectorized thermoplastic road marking of 150mm width including glass bead embedding.', 'unit' => 'Meter', 'unit_rate' => 6.00, 'tax_code' => '11', 'category' => 'Road Marking'],
            ['name' => 'Thermoplastic Solid Line', 'description' => 'Continuous solid thermoplastic line marking including surface preparation and reflectorized beads.', 'unit' => 'Meter', 'unit_rate' => 5.00, 'tax_code' => '11', 'category' => 'Road Marking'],
            ['name' => 'Thermoplastic Broken Line', 'description' => 'Broken/dashed thermoplastic line marking with uniform gap spacing including reflectorized beads.', 'unit' => 'Meter', 'unit_rate' => 4.50, 'tax_code' => '11', 'category' => 'Road Marking'],
            ['name' => 'Glass Beads Application', 'description' => 'Application of reflectorized glass beads onto thermoplastic markings for enhanced night visibility.', 'unit' => 'Sqm', 'unit_rate' => 2.50, 'tax_code' => '11', 'category' => 'Road Marking'],
            ['name' => 'Premixed Road Marking', 'description' => 'Premixed road marking paint application for temporary or permanent markings on road surfaces.', 'unit' => 'Meter', 'unit_rate' => 3.00, 'tax_code' => '11', 'category' => 'Road Marking'],
            ['name' => 'Road Stud Installation', 'description' => 'Installation of reflectorized road studs on road surface including adhesive fixing and quality check.', 'unit' => 'Each', 'unit_rate' => 8.00, 'tax_code' => '11', 'category' => 'Road Marking'],
            ['name' => 'Arrow Marking', 'description' => 'Thermoplastic directional arrow marking on road surface for lane guidance and traffic management.', 'unit' => 'Each', 'unit_rate' => 25.00, 'tax_code' => '11', 'category' => 'Road Marking'],
            ['name' => 'Text/Symbol Marking', 'description' => 'Custom text or symbol marking such as BUS, TAXI, STOP or bicycle symbols on road surface.', 'unit' => 'Each', 'unit_rate' => 35.00, 'tax_code' => '11', 'category' => 'Road Marking'],
            ['name' => 'Hazard Marking', 'description' => 'Yellow/black hazard marking for road islands, curbs, and obstacles to improve driver awareness.', 'unit' => 'Sqm', 'unit_rate' => 12.00, 'tax_code' => '11', 'category' => 'Road Marking'],
            ['name' => 'Road Marking Removal', 'description' => 'Removal of existing road markings by grinding or water jetting including surface cleanup.', 'unit' => 'Sqm', 'unit_rate' => 6.00, 'tax_code' => '11', 'category' => 'Road Marking'],

            // ── General ──
            ['name' => 'Traffic Management Setup', 'description' => 'Deployment of traffic cones, signage, barriers, and flaggers for work zone traffic control and public safety.', 'unit' => 'Day', 'unit_rate' => 350.00, 'tax_code' => '11', 'category' => 'General'],
            ['name' => 'Site Safety Barriers', 'description' => 'Installation of water-filled or concrete safety barriers for work zone protection and traffic segregation.', 'unit' => 'Meter', 'unit_rate' => 15.00, 'tax_code' => '11', 'category' => 'General'],
            ['name' => 'Equipment Mobilization', 'description' => 'Transportation and mobilization of heavy equipment including paver, roller, and support vehicles to site.', 'unit' => 'Trip', 'unit_rate' => 2000.00, 'tax_code' => '11', 'category' => 'General'],
            ['name' => 'Site Supervision', 'description' => 'Qualified site supervisor for works coordination, safety monitoring, and quality control throughout project duration.', 'unit' => 'Hour', 'unit_rate' => 80.00, 'tax_code' => '11', 'category' => 'General'],
            ['name' => 'Quality Control Testing', 'description' => 'Laboratory and field testing of materials and workmanship including density, grade, and compaction tests.', 'unit' => 'Lot', 'unit_rate' => 500.00, 'tax_code' => '11', 'category' => 'General'],
            ['name' => 'Traffic Cone Placement', 'description' => 'Placement of reflective traffic cones for lane closure, work zone delineation, and traffic guidance.', 'unit' => 'Each', 'unit_rate' => 5.00, 'tax_code' => '11', 'category' => 'General'],
        ];

        foreach ($items as $item) {
            ServiceCatalogItem::create($item);
        }

        echo '  Seeded '.count($items)." service catalog items.\n";
    }
}
