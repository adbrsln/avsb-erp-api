<?php

namespace Database\Seeders;

use App\Helpers\MalaysianDataGenerator as G;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quotation;
use App\Services\NumberingService;

class BulkFinanceSeeder
{
    public function run(): void
    {
        $projects = Project::all();
        if ($projects->isEmpty()) {
            return;
        }

        $numService = new NumberingService;

        // 50 Quotations with items
        for ($i = 0; $i < 50; $i++) {
            $project = $projects->random();
            $subtotal = G::randomAmount(20000, 500000);
            $sst = round($subtotal * 0.08, 2);

            // Generate line items as JSON
            $itemList = [];
            $numItems = rand(2, 4);
            for ($j = 0; $j < $numItems; $j++) {
                $qty = rand(10, 500);
                $rate = G::randomAmount(20, 200);
                $itemList[] = [
                    'description' => ['Paving works', 'Milling works', 'Road marking', 'Site preparation', 'Drainage works', 'Supply of materials'][array_rand([0, 1, 2, 3, 4, 5])],
                    'unit' => ['Sqm', 'Ton', 'Meter', 'Unit', 'Trip'][array_rand([0, 1, 2, 3, 4])],
                    'quantity' => $qty,
                    'unit_rate' => $rate,
                    'total' => round($qty * $rate, 2),
                ];
            }

            $q = Quotation::create([
                'quote_number' => $numService->generate('quote'),
                'project_id' => $project->id,
                'client' => $project->client,
                'date' => G::randomDate('2024-01-01', '2024-12-31'),
                'status' => ['draft', 'sent', 'converted', 'draft', 'sent'][array_rand([0, 1, 2, 3, 4])],
                'subtotal' => $subtotal,
                'sst' => $sst,
                'total' => $subtotal + $sst,
                'items' => $itemList,
            ]);
        }

        // 30 Contracts
        for ($i = 0; $i < 30; $i++) {
            $project = $projects->random();
            $sstRate = 8.00;
            $retentionRate = 5.00;

            // Back-calculate subtotal from random net total
            $netTotal = G::randomAmount(50000, 1000000);
            $rateFactor = 1 + ($sstRate / 100) - ($retentionRate / 100);
            $subtotal = round($netTotal / $rateFactor, 2);
            $sst = round($subtotal * $sstRate / 100, 2);
            $retention = round($subtotal * $retentionRate / 100, 2);
            $total = round($subtotal + $sst - $retention, 2);

            $c = Contract::create([
                'contract_number' => $numService->generate('contract'),
                'project_id' => $project->id,
                'client' => $project->client,
                'date' => G::randomDate('2024-01-01', '2024-12-31'),
                'status' => ['active', 'draft', 'completed', 'active', 'active'][array_rand([0, 1, 2, 3, 4])],
                'total_amount' => $total,
                'subtotal' => $subtotal,
                'sst_rate' => $sstRate,
                'retention_rate' => $retentionRate,
                'terms' => 'Payment within 30 days. 5% retention for 6 months.',
                'billing_milestones' => [
                    ['description' => 'Mobilization', 'percentage' => 30, 'amount' => round($total * 0.3, 2), 'status' => 'pending'],
                    ['description' => 'Progress Payment', 'percentage' => 50, 'amount' => round($total * 0.5, 2), 'status' => 'pending'],
                    ['description' => 'Final Payment', 'percentage' => 20, 'amount' => round($total * 0.2, 2), 'status' => 'pending'],
                ],
            ]);
        }

        // 50 Invoices
        $contracts = Contract::all();
        for ($i = 0; $i < 50; $i++) {
            $project = $projects->random();
            $contract = $contracts->random();
            $subtotal = G::randomAmount(10000, 200000);
            $sst = round($subtotal * 0.08, 2);
            $retention = round($subtotal * 0.05, 2);

            Invoice::create([
                'invoice_number' => $numService->generate('invoice'),
                'contract_id' => $contract->id,
                'project_id' => $project->id,
                'client' => $project->client,
                'date' => G::randomDate('2024-01-01', '2024-12-31'),
                'due_date' => G::randomDate('2024-02-01', '2025-01-31'),
                'status' => ['draft', 'sent', 'paid', 'overdue', 'sent', 'paid'][array_rand([0, 1, 2, 3, 4, 5])],
                'subtotal' => $subtotal,
                'sst' => $sst,
                'retention' => $retention,
                'total' => $subtotal + $sst - $retention,
            ]);
        }
    }
}
