<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quotation;

class FinanceSeeder
{
    public function run(): void
    {
        if (Quotation::count() > 0) {
            return;
        }

        $projects = Project::all();
        $p1 = $projects->where('name', 'Jalan Tun Razak Resurfacing')->first() ?? $projects->first();
        $p2 = $projects->where('name', 'Federal Highway Patch Repair')->first() ?? ($projects->skip(1)->first() ?? $projects->first());

        if (! $p1) {
            return;
        }

        $q1 = Quotation::create([
            'quote_number' => 'QTN-2024-001',
            'project_id' => $p1->id,
            'client' => 'DBKL',
            'date' => '2024-01-10',
            'status' => 'converted',
            'subtotal' => 256000,
            'sst' => 20480,
            'total' => 276480,
            'items' => [
                ['description' => 'ACW14 Asphalt Paving 50mm', 'unit' => 'Sqm', 'quantity' => 5000, 'unit_rate' => 48, 'total' => 240000],
                ['description' => 'Tack Coat', 'unit' => 'Sqm', 'quantity' => 5000, 'unit_rate' => 3.2, 'total' => 16000],
            ],
        ]);

        $q2 = Quotation::create([
            'quote_number' => 'QTN-2024-002',
            'project_id' => $p2->id,
            'client' => 'LLM',
            'date' => '2024-01-20',
            'status' => 'draft',
            'subtotal' => 88000,
            'sst' => 7040,
            'total' => 95040,
            'items' => [
                ['description' => 'Milling 40mm', 'unit' => 'Sqm', 'quantity' => 2000, 'unit_rate' => 22, 'total' => 44000],
                ['description' => 'Disposal', 'unit' => 'Trip', 'quantity' => 100, 'unit_rate' => 350, 'total' => 35000],
                ['description' => 'Mobilization', 'unit' => 'Setup', 'quantity' => 1, 'unit_rate' => 5000, 'total' => 5000],
            ],
        ]);

        $c1 = Contract::create([
            'contract_number' => 'CNT-2024-001',
            'project_id' => $p1->id,
            'client' => 'DBKL',
            'date' => '2024-01-12',
            'status' => 'active',
            'total_amount' => 256000,
            'subtotal' => 248543.69,
            'sst_rate' => 8.00,
            'retention_rate' => 5.00,
            'terms' => 'Payment within 30 days of invoice. 5% retention for 6 months.',
        ]);

        $c2 = Contract::create([
            'contract_number' => 'CNT-2024-002',
            'project_id' => $p2->id,
            'client' => 'LLM',
            'date' => '2024-01-25',
            'status' => 'draft',
            'total_amount' => 88000,
            'subtotal' => 85436.89,
            'sst_rate' => 8.00,
            'retention_rate' => 5.00,
        ]);

        Invoice::create([
            'invoice_number' => 'INV-2024-001',
            'contract_id' => $c1->id,
            'project_id' => $p1->id,
            'client' => 'DBKL',
            'date' => '2024-01-20',
            'due_date' => '2024-02-19',
            'status' => 'paid',
            'subtotal' => 38400,
            'sst' => 3072,
            'retention' => 1920,
            'total' => 39552,
            'processed_at' => '2024-02-01 10:00:00',
        ]);
    }
}
