<?php

namespace Database\Seeders;

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

        Quotation::factory()
            ->count(50)
            ->sequence(function () use ($projects, $numService) {
                $project = $projects->random();

                return [
                    'quote_number' => $numService->generate('quote'),
                    'project_id' => $project->id,
                    'client' => $project->client,
                    'status' => fake()->randomElement(['draft', 'sent', 'converted', 'draft', 'sent']),
                ];
            })
            ->create();

        Contract::factory()
            ->count(30)
            ->sequence(function () use ($projects, $numService) {
                $project = $projects->random();
                $netTotal = fake()->randomFloat(2, 50000, 1000000);
                $sstRate = 8.00;
                $retentionRate = 5.00;
                $rateFactor = 1 + ($sstRate / 100) - ($retentionRate / 100);
                $subtotal = round($netTotal / $rateFactor, 2);
                $sst = round($subtotal * $sstRate / 100, 2);
                $retention = round($subtotal * $retentionRate / 100, 2);
                $total = round($subtotal + $sst - $retention, 2);

                return [
                    'contract_number' => $numService->generate('contract'),
                    'project_id' => $project->id,
                    'client' => $project->client,
                    'status' => fake()->randomElement(['active', 'draft', 'completed', 'active', 'active']),
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
                ];
            })
            ->create();

        $contracts = Contract::all();
        Invoice::factory()
            ->count(50)
            ->sequence(function () use ($projects, $contracts, $numService) {
                $project = $projects->random();
                $subtotal = fake()->randomFloat(2, 10000, 200000);
                $sst = round($subtotal * 0.08, 2);
                $retention = round($subtotal * 0.05, 2);

                return [
                    'invoice_number' => $numService->generate('invoice'),
                    'contract_id' => $contracts->random()->id,
                    'project_id' => $project->id,
                    'client' => $project->client,
                    'status' => fake()->randomElement(['draft', 'sent', 'paid', 'overdue', 'sent', 'paid']),
                    'subtotal' => $subtotal,
                    'sst' => $sst,
                    'retention' => $retention,
                    'total' => round($subtotal + $sst - $retention, 2),
                ];
            })
            ->create();
    }
}
