<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientPIC;
use App\Models\Phase;
use App\Models\PhaseTemplate;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\StaffProfile;
use App\Models\Task;
use App\Services\NumberingService;

class ExtraProjectSeeder
{
    public function run(): void
    {
        if (Project::count() > 5) {
            return;
        }

        $staff = StaffProfile::all();
        $pm = $staff->where('email', 'ahmadi@example.com')->first() ?? $staff->first();
        $projectTypes = ProjectType::all();

        // Additional clients for extra projects
        $extraClients = [
            ['code' => 'CLT-DBKL-001', 'name' => 'DBKL (Kuala Lumpur City Hall)', 'reg' => 'DBKL-1960', 'phone' => '03-2617 9000', 'email' => 'procurement@dbkl.gov.my', 'addr' => 'Menara DBKL 1, Jalan Raja Laut, 50350 Kuala Lumpur'],
            ['code' => 'CLT-LLM-001', 'name' => 'Lembaga Lebuhraya Malaysia (LLM)', 'reg' => 'LLM-1980', 'phone' => '03-2716 1000', 'email' => 'tender@llm.gov.my', 'addr' => 'KM 6, Jalan Serdang - Kajang, 43000 Kajang, Selangor'],
            ['code' => 'CLT-PLUS-001', 'name' => 'PLUS Malaysia Berhad', 'reg' => 'PLUS-1984', 'phone' => '03-2716 8000', 'email' => 'contracts@plus.com.my', 'addr' => 'Persimpangan Bertingkat Subang, KM 15, 47300 Petaling Jaya, Selangor'],
            ['code' => 'CLT-MMC-001', 'name' => 'MMC-Gamuda KVMRT Sdn Bhd', 'reg' => 'MMCG-2011', 'phone' => '03-2614 8000', 'email' => 'procurement@mmc-gamuda.com.my', 'addr' => 'Level 8, Menara Prestige, Jalan Pinang, 50450 Kuala Lumpur'],
        ];

        $clientRecords = [];
        foreach ($extraClients as $ec) {
            $client = Client::firstOrCreate(
                ['client_code' => $ec['code']],
                [
                    'company_name' => $ec['name'],
                    'registration_no' => $ec['reg'],
                    'phone' => $ec['phone'],
                    'email' => $ec['email'],
                    'address' => $ec['addr'],
                    'status' => 'active',
                ]
            );
            $clientRecords[] = $client;

            // Add PIC for each
            if (ClientPIC::where('client_id', $client->id)->count() === 0) {
                ClientPIC::create([
                    'client_id' => $client->id,
                    'name' => 'Procurement Officer',
                    'email' => 'procurement@'.strtolower(str_replace(' ', '', $ec['name'])).'.my',
                    'phone' => $ec['phone'],
                    'job_title' => 'Procurement Officer',
                    'department' => 'Procurement',
                    'is_primary' => true,
                ]);
            }
        }

        // Extra projects
        $extraProjects = [
            ['name' => 'Lebuhraya NKVE Resurfacing Phase 2', 'client' => $extraClients[2] ?? $clientRecords[0] ?? null, 'type_code' => 'paving', 'location' => 'NKVE Highway, Km 15-25', 'budget' => 1200000, 'po' => 'PO-PLUS-2024-002', 'start' => '2024-04-01', 'end' => '2024-07-30'],
            ['name' => 'DBKL Road Marking Program 2024', 'client' => $extraClients[0] ?? $clientRecords[0] ?? null, 'type_code' => 'road_marking', 'location' => 'Various roads, KL CBD', 'budget' => 450000, 'po' => 'PO-DBKL-2024-001', 'start' => '2024-05-01', 'end' => '2024-08-31'],
            ['name' => 'MRT Putrajaya Line Access Road Paving', 'client' => $extraClients[3] ?? $clientRecords[0] ?? null, 'type_code' => 'paving', 'location' => 'MRT Putrajaya Line Depot, Serdang', 'budget' => 850000, 'po' => 'PO-MMCG-2024-001', 'start' => '2024-03-15', 'end' => '2024-06-30'],
            ['name' => 'Federal Highway Milling & Overlay Package 3', 'client' => $extraClients[1] ?? $clientRecords[0] ?? null, 'type_code' => 'mill_pave', 'location' => 'Federal Highway, Km 5-12', 'budget' => 2100000, 'po' => 'PO-LLM-2024-002', 'start' => '2024-06-01', 'end' => '2024-09-30'],
            ['name' => 'TNB Subang Jaya Station Road Improvement', 'client' => null, 'type_code' => 'drainage', 'location' => 'TNB Subang Jaya Station', 'budget' => 280000, 'po' => 'PO-TNB-2024-005', 'start' => '2024-05-15', 'end' => '2024-07-15'],
            ['name' => 'Shah Alam Industrial Park Paving', 'client' => null, 'type_code' => 'paving', 'location' => 'Seksyen 26, Shah Alam', 'budget' => 680000, 'po' => 'PO-SHAH-2024-001', 'start' => '2024-04-15', 'end' => '2024-08-15'],
        ];

        $templates = PhaseTemplate::all();
        $statusOptions = ['active', 'planning', 'active', 'planning'];

        foreach ($extraProjects as $ep) {
            $type = $projectTypes->where('code', $ep['type_code'])->first() ?? $projectTypes->first();
            $client = $ep['client'];

            $project = Project::create([
                'name' => $ep['name'],
                'project_code' => (new NumberingService)->generate('project'),
                'client' => $client ? $client->company_name : 'TNB',
                'client_id' => $client ? $client->id : 1,
                'project_manager_id' => $pm->id ?? 1,
                'service_type_id' => 2,
                'po_number' => $ep['po'],
                'location' => $ep['location'],
                'status' => $statusOptions[array_rand($statusOptions)],
                'budget_amount' => $ep['budget'],
                'start_date' => $ep['start'],
                'end_date' => $ep['end'],
            ]);

            // Link project type
            if ($type) {
                $project->projectTypes()->syncWithoutDetaching([$type->id]);
            }

            // Create phases from templates
            $phaseTemplates = $templates->isNotEmpty()
                ? $templates->random(min(4, $templates->count()))
                : collect();

            foreach ($phaseTemplates as $idx => $tpl) {
                $phaseStart = date('Y-m-d', strtotime($ep['start'].' +'.($idx * 14).' days'));
                $phaseEnd = date('Y-m-d', strtotime($phaseStart.' +13 days'));

                Phase::create([
                    'project_id' => $project->id,
                    'name' => $tpl->name,
                    'order' => $idx + 1,
                    'status' => $idx === 0 ? 'in_progress' : 'pending',
                    'start_date' => $phaseStart,
                    'end_date' => $phaseEnd,
                ]);
            }

            // Create some tasks for the first phase
            $firstPhase = Phase::where('project_id', $project->id)->orderBy('order')->first();
            if ($firstPhase) {
                $taskTitles = ['Site mobilization', 'Traffic management setup', 'Equipment preparation', 'Material delivery coordination', 'Safety briefing'];
                foreach ($taskTitles as $tIdx => $tt) {
                    Task::create([
                        'phase_id' => $firstPhase->id,
                        'title' => $tt,
                        'description' => $tt.' for '.$ep['name'],
                        'status' => $tIdx < 2 ? 'completed' : ($tIdx < 4 ? 'todo' : 'todo'),
                        'priority' => $tIdx < 2 ? 'high' : 'medium',
                        'start_date' => $firstPhase->start_date,
                        'end_date' => $firstPhase->end_date,
                        'assigned_to' => $pm->id ?? 1,
                    ]);
                }
            }
        }
    }
}
