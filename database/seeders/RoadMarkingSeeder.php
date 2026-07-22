<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\InventoryItem;
use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectMaterialUsage;
use App\Models\ProjectType;
use App\Models\StaffProfile;
use App\Services\NumberingService;

class RoadMarkingSeeder
{
    public function run(): void
    {
        $jsonPath = __DIR__.'/../../database/sample/road-marking.json';
        if (! file_exists($jsonPath)) {
            echo "  [RoadMarkingSeeder] Skipped: road-marking.json not found\n";

            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        $transactions = $data['road_marking_projects']['transactions'] ?? [];
        if (empty($transactions)) {
            echo "  [RoadMarkingSeeder] Skipped: no transactions in JSON\n";

            return;
        }

        $type = ProjectType::where('code', 'road_marking')->first();
        if (! $type) {
            echo "  [RoadMarkingSeeder] ERROR: Road Marking project type not found\n";

            return;
        }

        $paintItem = InventoryItem::where('sku', 'RM-TP-001')->first();
        $glassItem = InventoryItem::where('sku', 'RM-GB-001')->first();
        $pm = StaffProfile::first();

        $created = 0;

        foreach ($transactions as $item) {
            $location = $item['lokasi'] ?? 'Unknown location';
            $projectCode = (new NumberingService)->generate('project');
            $tarikh = $this->parseDate($item['tarikh'] ?? null);

            // Create or retrieve client from contractor name
            $clientName = $item['nama_kontraktor'] ?? 'Unknown';
            $client = Client::firstOrCreate(
                ['company_name' => $clientName],
                [
                    'company_name' => $clientName,
                    'client_code' => (new NumberingService)->generate('client'),
                    'status' => 'active',
                ]
            );

            $extra = [
                'subcontractor' => $clientName,
                'paint_cost' => $item['kos_tepung_rm'] ?? null,
                'paint_qty' => $item['kos_tepung_detail'] ?? null,
                'glass_cost' => $item['kaca_rm'] ?? null,
                'glass_qty' => $item['kaca_detail'] ?? null,
                'labour_cost' => $item['gaji_pekerja_rm'] ?? null,
                'food_cost' => $item['makan_minum_rm'] ?? null,
                'profit' => $item['keuntungan_rm'] ?? null,
                'inv_no' => $item['inv_no'] ?? null,
                'paid_date' => $item['status'] ?? null,
            ];

            $project = Project::create([
                'name' => $location,
                'project_code' => $projectCode,
                'client' => $clientName,
                'client_id' => $client->id,
                'project_manager_id' => $pm?->id,
                'service_type_id' => 1,
                'location' => $location,
                'status' => 'completed',
                'budget_amount' => (float) ($item['invoice_claim_rm'] ?? 0),
                'start_date' => $tarikh,
                'end_date' => $tarikh,
                'description' => json_encode($extra, JSON_UNESCAPED_UNICODE),
            ]);

            $project->projectTypes()->sync([$type->id]);

            // Calculate project timeline for phase distribution
            $projBase = $tarikh ?: date('Y-m-d');
            $baseTs = strtotime($projBase);

            // All 6 phases distributed evenly across a 10-day window from project date
            $allPhases = [
                ['code' => 'site_visit'],
                ['code' => 'cleaning'],
                ['code' => 'marking'],
                ['code' => 'glass_beads'],
                ['code' => 'qc'],
                ['code' => 'send_invoice'],
            ];

            $numPhases = count($allPhases);
            $totalSpanDays = 10;
            $segmentDays = $totalSpanDays / max($numPhases - 1, 1);

            $markingPhaseId = null;

            foreach ($allPhases as $idx => $p) {
                $phaseStart = date('Y-m-d', (int) ($baseTs + ($idx * $segmentDays * 86400)));
                $phaseEnd = date('Y-m-d', (int) (strtotime($phaseStart) + max(86400, $segmentDays * 86400 * 0.5)));

                $phase = Phase::create([
                    'project_id' => $project->id,
                    'name' => $this->phaseDisplayName($p['code']),
                    'order' => $idx + 1,
                    'status' => 'completed',
                    'start_date' => $phaseStart,
                    'end_date' => $phaseEnd,
                    'completed_at' => $phaseEnd.' 17:00:00',
                    'completed_by' => $pm?->id,
                    'completion_remarks' => 'System override',
                ]);
                if ($p['code'] === 'marking') {
                    $markingPhaseId = $phase->id;
                }
            }

            // Create material usage records where qty detail is available
            $paintQty = $this->parseQty($item['kos_tepung_detail'] ?? null);
            $glassQty = $this->parseQty($item['kaca_detail'] ?? null);

            if ($paintQty && $paintItem) {
                $totalCost = $paintQty * ($paintItem->unit_cost ?: 50);
                ProjectMaterialUsage::create([
                    'project_id' => $project->id,
                    'phase_id' => $markingPhaseId,
                    'item_id' => $paintItem->id,
                    'qty' => $paintQty,
                    'unit_cost' => $paintItem->unit_cost ?: 50,
                    'total_cost' => $totalCost,
                    'notes' => 'Thermoplastic paint for road marking',
                    'created_by' => $pm?->id,
                ]);
            }

            if ($glassQty && $glassItem) {
                $totalCost = $glassQty * ($glassItem->unit_cost ?: 65);
                ProjectMaterialUsage::create([
                    'project_id' => $project->id,
                    'phase_id' => $markingPhaseId,
                    'item_id' => $glassItem->id,
                    'qty' => $glassQty,
                    'unit_cost' => $glassItem->unit_cost ?: 65,
                    'total_cost' => $totalCost,
                    'notes' => 'Glass beads for road marking',
                    'created_by' => $pm?->id,
                ]);
            }

            $created++;
        }

        echo "  [RoadMarkingSeeder] Created {$created} Road Marking projects\n";
    }

    private function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', trim($value), $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        return null;
    }

    private function parseQty(?string $value): ?float
    {
        if (! $value) {
            return null;
        }
        if (preg_match('/^([\d.]+)/', trim($value), $m)) {
            return (float) $m[1];
        }

        return null;
    }

    private function phaseDisplayName(string $code): string
    {
        return match ($code) {
            'site_visit' => 'Site Visit',
            'cleaning' => 'Road Cleaning',
            'marking' => 'Paint Marking',
            'glass_beads' => 'Glass Beads',
            'qc' => 'Quality Check',
            'send_invoice' => 'Send Invoice',
            default => ucwords(str_replace('_', ' ', $code)),
        };
    }
}
