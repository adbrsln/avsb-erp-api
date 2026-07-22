<?php

namespace App\Seeds;

use App\Models\ChecklistItem;
use App\Models\ChecklistResult;
use App\Models\StaffProfile;

class ChecklistSeeder
{
    public function run(): void
    {
        ChecklistItem::insert([
            // Phase 1 (Preparation) — 5 items
            ['phase_id' => 1, 'name' => 'Site safety barriers installed', 'description' => 'All perimeter barriers and warning signs in place', 'is_required' => true],
            ['phase_id' => 1, 'name' => 'Traffic management plan in place', 'description' => 'Traffic cones, diversion signs, and flaggers deployed', 'is_required' => true],
            ['phase_id' => 1, 'name' => 'Equipment pre-operation check done', 'description' => 'All machinery inspected and operational', 'is_required' => true],
            ['phase_id' => 1, 'name' => 'Utility markups verified', 'description' => 'Underground utilities clearly marked on ground', 'is_required' => true],
            ['phase_id' => 1, 'name' => 'Site cleaning completed', 'description' => 'Debris and vegetation cleared from work area', 'is_required' => false],

            // Phase 2 (Paving) — 5 items
            ['phase_id' => 2, 'name' => 'Tack coat application temperature correct', 'description' => 'Tack coat temperature within 40-70°C range', 'is_required' => true],
            ['phase_id' => 2, 'name' => 'Asphalt temperature within spec', 'description' => 'Asphalt delivery temperature between 140-160°C', 'is_required' => true],
            ['phase_id' => 2, 'name' => 'Compaction passes completed', 'description' => 'Breakdown, intermediate, and finish rolling completed', 'is_required' => true],
            ['phase_id' => 2, 'name' => 'Surface smoothness checked', 'description' => 'Surface checked with 3m straightedge — max 5mm deviation', 'is_required' => false],
            ['phase_id' => 2, 'name' => 'Joint alignment verified', 'description' => 'Longitudinal and transverse joints aligned properly', 'is_required' => true],

            // Phase 3 (Finishing) — 4 items
            ['phase_id' => 3, 'name' => 'Surface cleaned and swept', 'description' => 'Final surface sweeping completed', 'is_required' => true],
            ['phase_id' => 3, 'name' => 'Line markings applied', 'description' => 'Road markings applied per traffic plan', 'is_required' => true],
            ['phase_id' => 3, 'name' => 'Surface defects repaired', 'description' => 'Any surface defects patched and compacted', 'is_required' => false],
            ['phase_id' => 3, 'name' => 'Work zone demobilized', 'description' => 'All barriers, cones, and signage removed', 'is_required' => true],

            // Phase 4 (Site Prep — Project 2) — 4 items
            ['phase_id' => 4, 'name' => 'Utility markups verified', 'description' => 'Gas, water, power lines identified and marked', 'is_required' => true],
            ['phase_id' => 4, 'name' => 'Dust suppression active', 'description' => 'Water truck or spray system operational', 'is_required' => false],
            ['phase_id' => 4, 'name' => 'Site access secured', 'description' => 'Temporary access roads and entry points secured', 'is_required' => true],
            ['phase_id' => 4, 'name' => 'Safety briefing completed', 'description' => 'Toolbox talk completed with all crew members', 'is_required' => true],

            // Phase 5 (Milling — Project 2) — 3 items
            ['phase_id' => 5, 'name' => 'Milling depth verified', 'description' => 'Depth sensors calibrated and test pass measured', 'is_required' => true],
            ['phase_id' => 5, 'name' => 'Milled surface inspected', 'description' => 'No loose edges or step defects', 'is_required' => true],
            ['phase_id' => 5, 'name' => 'Debris removed from site', 'description' => 'Haulage trucks loaded and dispatched', 'is_required' => false],

            // Phase 6 (Restoration — Project 2) — 3 items
            ['phase_id' => 6, 'name' => 'Tack coat applied', 'description' => 'Uniform tack coat applied to milled surface', 'is_required' => true],
            ['phase_id' => 6, 'name' => 'Asphalt laid and compacted', 'description' => 'Wearing course laid and compacted to spec', 'is_required' => true],
            ['phase_id' => 6, 'name' => 'Surface level checked', 'description' => 'Final surface level within ±5mm of design', 'is_required' => true],

            // Phase 7 (Cleaning — Project 3) — 3 items
            ['phase_id' => 7, 'name' => 'Surface cleaned thoroughly', 'description' => 'All loose debris, dust, and dirt removed', 'is_required' => true],
            ['phase_id' => 7, 'name' => 'Weather conditions suitable', 'description' => 'Dry surface, no rain forecast for 24 hours', 'is_required' => true],
            ['phase_id' => 7, 'name' => 'Traffic control in place', 'description' => 'Temporary traffic management deployed', 'is_required' => false],

            // Phase 8 (Marking — Project 3) — 3 items
            ['phase_id' => 8, 'name' => 'Marking layout verified', 'description' => 'Line positions checked against design drawings', 'is_required' => true],
            ['phase_id' => 8, 'name' => 'Paint/thermoplastic applied', 'description' => 'Application thickness and width within spec', 'is_required' => true],
            ['phase_id' => 8, 'name' => 'Reflector beads applied', 'description' => 'Glass beads evenly distributed on wet marking', 'is_required' => true],

            // Phase 9 (Inspection — Project 3) — 3 items
            ['phase_id' => 9, 'name' => 'Line visibility checked', 'description' => 'All markings clearly visible day and night', 'is_required' => true],
            ['phase_id' => 9, 'name' => 'Dimensions verified', 'description' => 'Line widths and lengths match specification', 'is_required' => true],
            ['phase_id' => 9, 'name' => 'Site cleaned and demobilized', 'description' => 'All equipment and materials removed from site', 'is_required' => false],
        ]);

        $adminId = StaffProfile::first()?->id ?? 1;
        ChecklistResult::insert([
            ['phase_id' => 1, 'checklist_item_id' => 1, 'passed' => true, 'remarks' => 'All barriers installed per JKR standard', 'checked_by' => $adminId, 'checked_at' => '2024-01-16 08:00:00'],
            ['phase_id' => 1, 'checklist_item_id' => 2, 'passed' => true, 'remarks' => 'TMP approved by DBKL', 'checked_by' => $adminId, 'checked_at' => '2024-01-16 08:15:00'],
            ['phase_id' => 1, 'checklist_item_id' => 3, 'passed' => true, 'remarks' => 'All equipment pre-check signed off', 'checked_by' => $adminId, 'checked_at' => '2024-01-16 08:30:00'],
            ['phase_id' => 1, 'checklist_item_id' => 4, 'passed' => true, 'remarks' => 'Utility markups confirmed with TNB', 'checked_by' => $adminId, 'checked_at' => '2024-01-16 09:00:00'],
        ]);
    }
}
