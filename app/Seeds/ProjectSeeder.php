<?php

namespace App\Seeds;

use App\Models\Client;
use App\Models\ClientPIC;
use App\Models\Phase;
use App\Models\Project;
use App\Models\StaffProfile;
use App\Models\Task;

class ProjectSeeder
{
    public function run(): void
    {
        if (Project::whereIn('project_code', ['PROJ-001', 'PROJ-002', 'PROJ-003'])->count() > 0) {
            return;
        }

        $pm = StaffProfile::first();
        $pmId = $pm?->id ?? 1;
        $staff2 = StaffProfile::all()->get(1)?->id ?? 2;
        $staff3 = StaffProfile::all()->get(2)?->id ?? 3;
        $staff4 = StaffProfile::all()->get(3)?->id ?? 4;

        // Create or find clients
        $clientData = [
            ['client_code' => 'CLT-MBPJ-001', 'company_name' => 'Majlis Bandaraya Petaling Jaya (MBPJ)', 'reg' => 'MBPJ-1964', 'phone' => '03-7956 3555', 'email' => 'procurement@mbpj.gov.my'],
            ['client_code' => 'CLT-LLM-001', 'company_name' => 'Lembaga Lebuhraya Malaysia (LLM)', 'reg' => 'LLM-1980', 'phone' => '03-2716 1000', 'email' => 'tender@llm.gov.my'],
            ['client_code' => 'CLT-PPJ-001', 'company_name' => 'Perbadanan Putrajaya (PPJ)', 'reg' => 'PPJ-1995', 'phone' => '03-8887 7000', 'email' => 'contracts@ppj.gov.my'],
        ];

        $clients = [];
        foreach ($clientData as $cd) {
            $client = Client::firstOrCreate(
                ['client_code' => $cd['client_code']],
                [
                    'company_name' => $cd['company_name'],
                    'registration_no' => $cd['reg'],
                    'phone' => $cd['phone'],
                    'email' => $cd['email'],
                    'status' => 'active',
                ]
            );
            $clients[$client->company_name] = $client;

            // Add PICs
            if (ClientPIC::where('client_id', $client->id)->count() === 0) {
                ClientPIC::create([
                    'client_id' => $client->id,
                    'name' => 'Procurement Officer',
                    'email' => 'po@'.strtolower(str_replace([' ', '(', ')'], '', $client->company_name)).'.my',
                    'phone' => $cd['phone'],
                    'job_title' => 'Procurement Officer',
                    'department' => 'Procurement',
                    'is_primary' => true,
                ]);
            }
        }

        $c1Id = $clients['Majlis Bandaraya Petaling Jaya (MBPJ)']->id ?? 1;
        $c2Id = $clients['Lembaga Lebuhraya Malaysia (LLM)']->id ?? 1;
        $c3Id = $clients['Perbadanan Putrajaya (PPJ)']->id ?? 1;

        $pic1 = ClientPIC::where('client_id', $c1Id)->first()?->id ?? 1;
        $pic2 = ClientPIC::where('client_id', $c2Id)->first()?->id ?? 1;
        $pic3 = ClientPIC::where('client_id', $c3Id)->first()?->id ?? 1;

        $p1 = Project::create([
            'name' => 'Jalan Tun Razak Resurfacing',
            'project_code' => 'PROJ-001',
            'client' => 'Majlis Bandaraya Petaling Jaya (MBPJ)',
            'client_id' => $c1Id,
            'client_pic_id' => $pic1,
            'project_manager_id' => $pmId,
            'service_type_id' => 2,
            'po_number' => 'PO-MBPJ-2024-001',
            'location' => 'Jalan Tun Razak, KL',
            'status' => 'active',
            'budget_amount' => 500000,
            'start_date' => '2024-01-15',
            'end_date' => '2024-04-15',
        ]);

        $p2 = Project::create([
            'name' => 'Federal Highway Patch Repair',
            'project_code' => 'PROJ-002',
            'client' => 'Lembaga Lebuhraya Malaysia (LLM)',
            'client_id' => $c2Id,
            'client_pic_id' => $pic2,
            'project_manager_id' => $pmId,
            'service_type_id' => 1,
            'po_number' => 'PO-LLM-2024-001',
            'location' => 'Federal Highway, Selangor',
            'status' => 'active',
            'budget_amount' => 350000,
            'start_date' => '2024-02-01',
            'end_date' => '2024-05-30',
        ]);

        $p3 = Project::create([
            'name' => 'Putrajaya Road Marking',
            'project_code' => 'PROJ-003',
            'client' => 'Perbadanan Putrajaya (PPJ)',
            'client_id' => $c3Id,
            'client_pic_id' => $pic3,
            'project_manager_id' => $pmId,
            'service_type_id' => 3,
            'po_number' => 'PO-PPJ-2024-001',
            'location' => 'Putrajaya',
            'status' => 'planning',
            'budget_amount' => 180000,
            'start_date' => '2024-03-01',
            'end_date' => '2024-06-30',
        ]);

        Phase::create(['project_id' => $p1->id, 'name' => 'Preparation', 'order' => 1, 'status' => 'completed', 'start_date' => '2024-01-15', 'end_date' => '2024-01-25']);
        $ph2 = Phase::create(['project_id' => $p1->id, 'name' => 'Paving', 'order' => 2, 'status' => 'in_progress', 'start_date' => '2024-01-26', 'end_date' => '2024-03-01']);
        $ph3 = Phase::create(['project_id' => $p1->id, 'name' => 'Finishing', 'order' => 3, 'status' => 'pending', 'start_date' => '2024-03-02', 'end_date' => '2024-04-15']);
        $ph4 = Phase::create(['project_id' => $p2->id, 'name' => 'Site Prep', 'order' => 1, 'status' => 'in_progress', 'start_date' => '2024-02-01', 'end_date' => '2024-02-15']);
        $ph5 = Phase::create(['project_id' => $p2->id, 'name' => 'Milling', 'order' => 2, 'status' => 'pending', 'start_date' => '2024-02-16', 'end_date' => '2024-03-15']);
        $ph6 = Phase::create(['project_id' => $p2->id, 'name' => 'Restoration', 'order' => 3, 'status' => 'pending', 'start_date' => '2024-03-16', 'end_date' => '2024-05-30']);
        $ph7 = Phase::create(['project_id' => $p3->id, 'name' => 'Cleaning', 'order' => 1, 'status' => 'pending', 'start_date' => '2024-03-01', 'end_date' => '2024-03-20']);
        $ph8 = Phase::create(['project_id' => $p3->id, 'name' => 'Marking', 'order' => 2, 'status' => 'pending', 'start_date' => '2024-03-21', 'end_date' => '2024-05-15']);
        $ph9 = Phase::create(['project_id' => $p3->id, 'name' => 'Inspection', 'order' => 3, 'status' => 'pending', 'start_date' => '2024-05-16', 'end_date' => '2024-06-30']);

        $phId1 = $p1->phases()->first()?->id ?? 1;
        $phId2 = $ph2->id;
        $phId4 = $ph4->id;

        Task::create(['phase_id' => $phId1, 'title' => 'Site Setup & Barricading', 'description' => 'Erect safety barriers and signage around work zone', 'status' => 'completed', 'priority' => 'high', 'start_date' => '2024-01-15', 'end_date' => '2024-01-16', 'actual_start' => '2024-01-15 07:00:00', 'actual_end' => '2024-01-16 16:00:00', 'assigned_to' => $staff2]);
        Task::create(['phase_id' => $phId1, 'title' => 'Traffic Management Setup', 'description' => 'Deploy traffic cones, signs, and flaggers', 'status' => 'completed', 'priority' => 'high', 'start_date' => '2024-01-15', 'end_date' => '2024-01-15', 'actual_start' => '2024-01-15 07:30:00', 'actual_end' => '2024-01-15 15:00:00', 'assigned_to' => $staff2]);
        Task::create(['phase_id' => $phId1, 'title' => 'Equipment Mobilization', 'description' => 'Move paver, roller, and milling machine to site', 'status' => 'completed', 'priority' => 'medium', 'start_date' => '2024-01-16', 'end_date' => '2024-01-17', 'actual_start' => '2024-01-16 08:00:00', 'actual_end' => '2024-01-17 12:00:00', 'assigned_to' => $staff2]);
        Task::create(['phase_id' => $phId2, 'title' => 'Tack Coat Spraying', 'description' => 'Apply tack coat before asphalt laying', 'status' => 'running', 'priority' => 'high', 'start_date' => '2024-02-01', 'end_date' => '2024-02-02', 'actual_start' => '2024-02-01 08:00:00', 'assigned_to' => $staff2]);
        Task::create(['phase_id' => $phId2, 'title' => 'ACW14 Asphalt Laying', 'description' => 'Lay ACW14 asphalt on prepared surface', 'status' => 'paused', 'pause_reason' => 'Weather', 'paused_at' => date('Y-m-d H:i:s'), 'priority' => 'high', 'start_date' => '2024-02-02', 'end_date' => '2024-02-05', 'actual_start' => '2024-02-02 09:00:00', 'assigned_to' => $staff2]);
        Task::create(['phase_id' => $phId2, 'title' => 'Compaction Pass 1', 'description' => 'Initial breakdown rolling', 'status' => 'completed', 'priority' => 'high', 'start_date' => '2024-02-02', 'end_date' => '2024-02-02', 'actual_start' => '2024-02-02 10:00:00', 'actual_end' => '2024-02-02 16:00:00', 'assigned_to' => $staff2]);
        Task::create(['phase_id' => $phId2, 'title' => 'Compaction Pass 2', 'description' => 'Intermediate rolling', 'status' => 'todo', 'priority' => 'medium', 'start_date' => '2024-02-03', 'end_date' => '2024-02-03', 'assigned_to' => $staff2]);
        Task::create(['phase_id' => $phId2, 'title' => 'Surface Smoothness Test', 'description' => 'Check with straightedge', 'status' => 'todo', 'priority' => 'medium', 'start_date' => '2024-02-04', 'end_date' => '2024-02-04', 'assigned_to' => $staff2]);
        Task::create(['phase_id' => $phId1, 'title' => 'Roadway Cleaning', 'description' => 'Sweep and clear debris from work area', 'status' => 'completed', 'priority' => 'low', 'start_date' => '2024-01-17', 'end_date' => '2024-01-17', 'actual_start' => '2024-01-17 08:00:00', 'actual_end' => '2024-01-17 12:00:00', 'assigned_to' => $staff2]);
        Task::create(['phase_id' => $phId1, 'title' => 'Utility Markup Verification', 'description' => 'Confirm all underground utilities are marked', 'status' => 'completed', 'priority' => 'high', 'start_date' => '2024-01-16', 'end_date' => '2024-01-16', 'actual_start' => '2024-01-16 13:00:00', 'actual_end' => '2024-01-16 17:00:00', 'assigned_to' => $staff2]);
        Task::create(['phase_id' => $phId4, 'title' => 'Milling Machine Prep', 'description' => 'Setup and calibrate Wirtgen mill', 'status' => 'todo', 'priority' => 'medium', 'start_date' => '2024-02-10', 'end_date' => '2024-02-10', 'assigned_to' => $staff3]);
        Task::create(['phase_id' => $phId4, 'title' => 'Milling 40mm Depth Pass 1', 'description' => 'First milling pass at 40mm depth', 'status' => 'todo', 'priority' => 'high', 'start_date' => '2024-02-10', 'end_date' => '2024-02-12', 'assigned_to' => $staff3]);
        Task::create(['phase_id' => $phId4, 'title' => 'Milling 40mm Depth Pass 2', 'description' => 'Second milling pass at 40mm depth', 'status' => 'todo', 'priority' => 'high', 'start_date' => '2024-02-12', 'end_date' => '2024-02-14', 'assigned_to' => $staff4]);
        Task::create(['phase_id' => $phId4, 'title' => 'Debris Removal & Hauling', 'description' => 'Load and haul milled material to disposal site', 'status' => 'todo', 'priority' => 'medium', 'start_date' => '2024-02-10', 'end_date' => '2024-02-14', 'assigned_to' => $staff3]);
    }
}
