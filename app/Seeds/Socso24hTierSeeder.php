<?php

namespace App\Seeds;

use App\Models\Socso24hTier;

class Socso24hTierSeeder
{
    public function run(): void
    {
        if (Socso24hTier::where('phase', 1)->count() > 0) {
            return;
        }

        $path = __DIR__.'/../../database/sample/socso24h-phase1.json';
        if (! file_exists($path)) {
            echo "  [Socso24hTierSeeder] Phase 1 data file not found, skipping.\n";

            return;
        }

        $brackets = json_decode(file_get_contents($path), true);
        if (! $brackets) {
            echo "  [Socso24hTierSeeder] Invalid JSON, skipping.\n";

            return;
        }

        $inserted = 0;
        foreach ($brackets as $b) {
            foreach (['first' => 'cat1', 'second' => 'cat2'] as $cat => $key) {
                Socso24hTier::create([
                    'category' => $cat,
                    'phase' => 1,
                    'wage_from' => $b['wage_from'],
                    'wage_to' => $b['wage_to'],
                    'employee_amount' => $b[$key],
                ]);
                $inserted++;
            }
        }

        echo "  Seeded {$inserted} SKBBK Phase 1 bracket rows.\n";
    }
}
