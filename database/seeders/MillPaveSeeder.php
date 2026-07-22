<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientPIC;
use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\StaffProfile;
use App\Services\NumberingService;

class MillPaveSeeder
{
    public function run(): void
    {
        $jsonPath = __DIR__.'/../../database/sample/mill-pave.json';
        if (! file_exists($jsonPath)) {
            echo "  [MillPaveSeeder] Skipped: mill-pave.json not found\n";

            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        $projects = $data['mill_and_pave_projects'] ?? [];
        if (empty($projects)) {
            echo "  [MillPaveSeeder] Skipped: no projects in JSON\n";

            return;
        }

        $tnb = Client::where('company_name', 'Tenaga Nasional Berhad')->first();
        if (! $tnb) {
            echo "  [MillPaveSeeder] ERROR: TNB client not found. Run ClientSeeder first.\n";

            return;
        }

        $paving = ProjectType::where('code', 'paving')->first();
        $milling = ProjectType::where('code', 'milling')->first();
        if (! $paving || ! $milling) {
            echo "  [MillPaveSeeder] ERROR: Paving/Milling project types not found. Run ProjectTypeSeeder first.\n";

            return;
        }

        $typeIds = [$paving->id, $milling->id];

        $pm = StaffProfile::first();

        foreach ($projects as $index => $item) {
            $projectCode = (new NumberingService)->generate('project');
            $picName = $item['pic_tnb'] ?? null;

            // Find or create TNB PIC (handle combined names like "Nur Jalilah / Nur Faogihah")
            $picId = null;
            if ($picName) {
                $primaryName = explode('/', $picName)[0];
                $primaryName = explode(' / ', $primaryName)[0];
                $primaryName = trim($primaryName);
                if ($primaryName && ! in_array(strtoupper($primaryName), ['SA', ''])) {
                    $pic = ClientPIC::firstOrCreate(
                        ['name' => $primaryName, 'client_id' => $tnb->id],
                        [
                            'client_id' => $tnb->id,
                            'name' => $primaryName,
                            'email' => strtolower(str_replace(' ', '.', $primaryName)).'@tnb.com.my',
                            'is_primary' => $index < 3,
                        ]
                    );
                    $picId = $pic->id;
                }
            }

            $startDate = $this->parseDate($item['start_date'] ?? null);
            $endDate = null;
            if ($startDate) {
                $endDate = date('Y-m-d', strtotime($startDate.' +7 days'));
            }

            $extra = [];
            if (! empty($item['tnb_station'])) {
                $extra['tnb_station'] = $item['tnb_station'];
            }
            if (! empty($item['area_m2_or_ls'])) {
                $extra['area_m2'] = $item['area_m2_or_ls'];
            }
            if (! empty($item['subcontractor'])) {
                $extra['subcontractor'] = $item['subcontractor'];
            }
            if (! empty($item['status'])) {
                $extra['tracking_status'] = $item['status'];
            }
            if (! empty($item['tnb_semakan'])) {
                $extra['tnb_semakan'] = $item['tnb_semakan'];
            }

            $notes = json_encode($extra, JSON_UNESCAPED_UNICODE);

            $project = Project::create([
                'name' => $item['site_location'],
                'project_code' => $projectCode,
                'client' => 'Tenaga Nasional Berhad',
                'client_id' => $tnb->id,
                'client_pic_id' => $picId,
                'project_manager_id' => $pm?->id,
                'service_type_id' => 1,
                'po_number' => $item['po_number'],
                'location' => $item['site_location'],
                'status' => 'completed',
                'budget_amount' => (float) ($item['po_value_rm'] ?? 0),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'description' => $notes,
            ]);

            // Sync project type
            $project->projectTypes()->sync($typeIds);

            // Calculate project timeline for phase distribution
            $projStart = $project->start_date;
            $projEnd = $project->end_date;
            if (! $projEnd) {
                $projEnd = date('Y-m-d', strtotime(($projStart ?: date('Y-m-d')).' +30 days'));
            }
            $startTs = strtotime($projStart);
            $endTs = strtotime($projEnd);
            $totalDays = max(1, (int) (($endTs - $startTs) / 86400));

            // All 9 phases distributed evenly across timeline
            $allPhases = [
                ['code' => 'site_visit'],
                ['code' => 'start_date'],
                ['code' => 'coring_test'],
                ['code' => 'lab_report'],
                ['code' => 'road_marking'],
                ['code' => 'jms'],
                ['code' => 'lks'],
                ['code' => 'tnb'],
                ['code' => 'se'],
            ];

            $numPhases = count($allPhases);
            $segmentDays = $totalDays / max($numPhases - 1, 1);

            foreach ($allPhases as $idx => $p) {
                $order = $idx + 1;
                $phaseStart = date('Y-m-d', (int) ($startTs + ($idx * $segmentDays * 86400)));
                $phaseEnd = date('Y-m-d', (int) (strtotime($phaseStart) + max(86400, $segmentDays * 86400 * 0.3)));
                $name = in_array($p['code'], ['jms', 'lks', 'tnb', 'se'])
                    ? strtoupper($p['code'])
                    : ucwords(str_replace('_', ' ', $p['code']));

                Phase::create([
                    'project_id' => $project->id,
                    'name' => $name,
                    'order' => $order,
                    'status' => 'completed',
                    'start_date' => $phaseStart,
                    'end_date' => $phaseEnd,
                    'completed_at' => $phaseEnd.' 17:00:00',
                    'completed_by' => $pm?->id,
                    'completion_remarks' => 'System override',
                ]);
            }
        }

        echo '  [MillPaveSeeder] Created '.count($projects).' Mill & Pave projects'.PHP_EOL;
    }

    private function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        // Handle dd.mm.yyyy format
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', trim($value), $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        // Handle dd/mm/yyyy
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', trim($value), $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        return null;
    }
}
