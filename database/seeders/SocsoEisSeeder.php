<?php

namespace Database\Seeders;

use App\Models\EisContributionTier;
use App\Models\SocsoContributionTier;
use Illuminate\Database\Seeder;

class SocsoEisSeeder extends Seeder
{
    public function run(): void
    {
        // SOCSO Class 1 (Monthly wages) — Third Schedule, Act 4 (2024 amendment, wage ceiling RM6,000)
        $socsoTiers = [
            ['wage_from' => 0.01, 'wage_to' => 1500.00, 'employer_amount' => 25.35, 'employee_amount' => 7.25],
            ['wage_from' => 1500.01, 'wage_to' => 1600.00, 'employer_amount' => 27.15, 'employee_amount' => 7.75],
            ['wage_from' => 1600.01, 'wage_to' => 1700.00, 'employer_amount' => 28.85, 'employee_amount' => 8.25],
            ['wage_from' => 1700.01, 'wage_to' => 1800.00, 'employer_amount' => 30.65, 'employee_amount' => 8.75],
            ['wage_from' => 1800.01, 'wage_to' => 1900.00, 'employer_amount' => 32.35, 'employee_amount' => 9.25],
            ['wage_from' => 1900.01, 'wage_to' => 2000.00, 'employer_amount' => 34.15, 'employee_amount' => 9.75],
            ['wage_from' => 2000.01, 'wage_to' => 2100.00, 'employer_amount' => 35.85, 'employee_amount' => 10.25],
            ['wage_from' => 2100.01, 'wage_to' => 2200.00, 'employer_amount' => 37.65, 'employee_amount' => 10.75],
            ['wage_from' => 2200.01, 'wage_to' => 2300.00, 'employer_amount' => 39.35, 'employee_amount' => 11.25],
            ['wage_from' => 2300.01, 'wage_to' => 2400.00, 'employer_amount' => 41.15, 'employee_amount' => 11.75],
            ['wage_from' => 2400.01, 'wage_to' => 2500.00, 'employer_amount' => 42.85, 'employee_amount' => 12.25],
            ['wage_from' => 2500.01, 'wage_to' => 2600.00, 'employer_amount' => 44.65, 'employee_amount' => 12.75],
            ['wage_from' => 2600.01, 'wage_to' => 2700.00, 'employer_amount' => 46.35, 'employee_amount' => 13.25],
            ['wage_from' => 2700.01, 'wage_to' => 2800.00, 'employer_amount' => 48.15, 'employee_amount' => 13.75],
            ['wage_from' => 2800.01, 'wage_to' => 2900.00, 'employer_amount' => 49.85, 'employee_amount' => 14.25],
            ['wage_from' => 2900.01, 'wage_to' => 3000.00, 'employer_amount' => 51.65, 'employee_amount' => 14.75],
            ['wage_from' => 3000.01, 'wage_to' => 3100.00, 'employer_amount' => 53.35, 'employee_amount' => 15.25],
            ['wage_from' => 3100.01, 'wage_to' => 3200.00, 'employer_amount' => 55.15, 'employee_amount' => 15.75],
            ['wage_from' => 3200.01, 'wage_to' => 3300.00, 'employer_amount' => 56.85, 'employee_amount' => 16.25],
            ['wage_from' => 3300.01, 'wage_to' => 3400.00, 'employer_amount' => 58.65, 'employee_amount' => 16.75],
            ['wage_from' => 3400.01, 'wage_to' => 3500.00, 'employer_amount' => 60.35, 'employee_amount' => 17.25],
            ['wage_from' => 3500.01, 'wage_to' => 3600.00, 'employer_amount' => 62.15, 'employee_amount' => 17.75],
            ['wage_from' => 3600.01, 'wage_to' => 3700.00, 'employer_amount' => 63.85, 'employee_amount' => 18.25],
            ['wage_from' => 3700.01, 'wage_to' => 3800.00, 'employer_amount' => 65.65, 'employee_amount' => 18.75],
            ['wage_from' => 3800.01, 'wage_to' => 3900.00, 'employer_amount' => 67.35, 'employee_amount' => 19.25],
            ['wage_from' => 3900.01, 'wage_to' => 4000.00, 'employer_amount' => 69.15, 'employee_amount' => 19.75],
            ['wage_from' => 4000.01, 'wage_to' => 4100.00, 'employer_amount' => 70.85, 'employee_amount' => 20.25],
            ['wage_from' => 4100.01, 'wage_to' => 4200.00, 'employer_amount' => 72.65, 'employee_amount' => 20.75],
            ['wage_from' => 4200.01, 'wage_to' => 4300.00, 'employer_amount' => 74.35, 'employee_amount' => 21.25],
            ['wage_from' => 4300.01, 'wage_to' => 4400.00, 'employer_amount' => 76.15, 'employee_amount' => 21.75],
            ['wage_from' => 4400.01, 'wage_to' => 4500.00, 'employer_amount' => 77.85, 'employee_amount' => 22.25],
            ['wage_from' => 4500.01, 'wage_to' => 4600.00, 'employer_amount' => 79.65, 'employee_amount' => 22.75],
            ['wage_from' => 4600.01, 'wage_to' => 4700.00, 'employer_amount' => 81.35, 'employee_amount' => 23.25],
            ['wage_from' => 4700.01, 'wage_to' => 4800.00, 'employer_amount' => 83.15, 'employee_amount' => 23.75],
            ['wage_from' => 4800.01, 'wage_to' => 4900.00, 'employer_amount' => 84.85, 'employee_amount' => 24.25],
            ['wage_from' => 4900.01, 'wage_to' => 5000.00, 'employer_amount' => 86.65, 'employee_amount' => 24.75],
            ['wage_from' => 5000.01, 'wage_to' => 5100.00, 'employer_amount' => 88.35, 'employee_amount' => 25.25],
            ['wage_from' => 5100.01, 'wage_to' => 5200.00, 'employer_amount' => 90.15, 'employee_amount' => 25.75],
            ['wage_from' => 5200.01, 'wage_to' => 5300.00, 'employer_amount' => 91.85, 'employee_amount' => 26.25],
            ['wage_from' => 5300.01, 'wage_to' => 5400.00, 'employer_amount' => 93.65, 'employee_amount' => 26.75],
            ['wage_from' => 5400.01, 'wage_to' => 5500.00, 'employer_amount' => 95.35, 'employee_amount' => 27.25],
            ['wage_from' => 5500.01, 'wage_to' => 5600.00, 'employer_amount' => 97.15, 'employee_amount' => 27.75],
            ['wage_from' => 5600.01, 'wage_to' => 5700.00, 'employer_amount' => 98.85, 'employee_amount' => 28.25],
            ['wage_from' => 5700.01, 'wage_to' => 5800.00, 'employer_amount' => 100.65, 'employee_amount' => 28.75],
            ['wage_from' => 5800.01, 'wage_to' => 5900.00, 'employer_amount' => 102.35, 'employee_amount' => 29.25],
            ['wage_from' => 5900.01, 'wage_to' => 6000.00, 'employer_amount' => 104.15, 'employee_amount' => 29.75],
        ];

        if (SocsoContributionTier::count() === 0) {
            SocsoContributionTier::insert($socsoTiers);
        }

        // EIS (Employment Insurance System) — Second Schedule, Act 800 (2024 amendment, wage ceiling RM6,000)
        $eisTiers = [
            ['wage_from' => 0.01, 'wage_to' => 1500.00, 'employer_amount' => 2.90, 'employee_amount' => 2.90],
            ['wage_from' => 1500.01, 'wage_to' => 1600.00, 'employer_amount' => 3.10, 'employee_amount' => 3.10],
            ['wage_from' => 1600.01, 'wage_to' => 1700.00, 'employer_amount' => 3.30, 'employee_amount' => 3.30],
            ['wage_from' => 1700.01, 'wage_to' => 1800.00, 'employer_amount' => 3.50, 'employee_amount' => 3.50],
            ['wage_from' => 1800.01, 'wage_to' => 1900.00, 'employer_amount' => 3.70, 'employee_amount' => 3.70],
            ['wage_from' => 1900.01, 'wage_to' => 2000.00, 'employer_amount' => 3.90, 'employee_amount' => 3.90],
            ['wage_from' => 2000.01, 'wage_to' => 2100.00, 'employer_amount' => 4.10, 'employee_amount' => 4.10],
            ['wage_from' => 2100.01, 'wage_to' => 2200.00, 'employer_amount' => 4.30, 'employee_amount' => 4.30],
            ['wage_from' => 2200.01, 'wage_to' => 2300.00, 'employer_amount' => 4.50, 'employee_amount' => 4.50],
            ['wage_from' => 2300.01, 'wage_to' => 2400.00, 'employer_amount' => 4.70, 'employee_amount' => 4.70],
            ['wage_from' => 2400.01, 'wage_to' => 2500.00, 'employer_amount' => 4.90, 'employee_amount' => 4.90],
            ['wage_from' => 2500.01, 'wage_to' => 2600.00, 'employer_amount' => 5.10, 'employee_amount' => 5.10],
            ['wage_from' => 2600.01, 'wage_to' => 2700.00, 'employer_amount' => 5.30, 'employee_amount' => 5.30],
            ['wage_from' => 2700.01, 'wage_to' => 2800.00, 'employer_amount' => 5.50, 'employee_amount' => 5.50],
            ['wage_from' => 2800.01, 'wage_to' => 2900.00, 'employer_amount' => 5.70, 'employee_amount' => 5.70],
            ['wage_from' => 2900.01, 'wage_to' => 3000.00, 'employer_amount' => 5.90, 'employee_amount' => 5.90],
            ['wage_from' => 3000.01, 'wage_to' => 3100.00, 'employer_amount' => 6.10, 'employee_amount' => 6.10],
            ['wage_from' => 3100.01, 'wage_to' => 3200.00, 'employer_amount' => 6.30, 'employee_amount' => 6.30],
            ['wage_from' => 3200.01, 'wage_to' => 3300.00, 'employer_amount' => 6.50, 'employee_amount' => 6.50],
            ['wage_from' => 3300.01, 'wage_to' => 3400.00, 'employer_amount' => 6.70, 'employee_amount' => 6.70],
            ['wage_from' => 3400.01, 'wage_to' => 3500.00, 'employer_amount' => 6.90, 'employee_amount' => 6.90],
            ['wage_from' => 3500.01, 'wage_to' => 3600.00, 'employer_amount' => 7.10, 'employee_amount' => 7.10],
            ['wage_from' => 3600.01, 'wage_to' => 3700.00, 'employer_amount' => 7.30, 'employee_amount' => 7.30],
            ['wage_from' => 3700.01, 'wage_to' => 3800.00, 'employer_amount' => 7.50, 'employee_amount' => 7.50],
            ['wage_from' => 3800.01, 'wage_to' => 3900.00, 'employer_amount' => 7.70, 'employee_amount' => 7.70],
            ['wage_from' => 3900.01, 'wage_to' => 4000.00, 'employer_amount' => 7.90, 'employee_amount' => 7.90],
            ['wage_from' => 4000.01, 'wage_to' => 4100.00, 'employer_amount' => 8.10, 'employee_amount' => 8.10],
            ['wage_from' => 4100.01, 'wage_to' => 4200.00, 'employer_amount' => 8.30, 'employee_amount' => 8.30],
            ['wage_from' => 4200.01, 'wage_to' => 4300.00, 'employer_amount' => 8.50, 'employee_amount' => 8.50],
            ['wage_from' => 4300.01, 'wage_to' => 4400.00, 'employer_amount' => 8.70, 'employee_amount' => 8.70],
            ['wage_from' => 4400.01, 'wage_to' => 4500.00, 'employer_amount' => 8.90, 'employee_amount' => 8.90],
            ['wage_from' => 4500.01, 'wage_to' => 4600.00, 'employer_amount' => 9.10, 'employee_amount' => 9.10],
            ['wage_from' => 4600.01, 'wage_to' => 4700.00, 'employer_amount' => 9.30, 'employee_amount' => 9.30],
            ['wage_from' => 4700.01, 'wage_to' => 4800.00, 'employer_amount' => 9.50, 'employee_amount' => 9.50],
            ['wage_from' => 4800.01, 'wage_to' => 4900.00, 'employer_amount' => 9.70, 'employee_amount' => 9.70],
            ['wage_from' => 4900.01, 'wage_to' => 5000.00, 'employer_amount' => 9.90, 'employee_amount' => 9.90],
            ['wage_from' => 5000.01, 'wage_to' => 5100.00, 'employer_amount' => 10.10, 'employee_amount' => 10.10],
            ['wage_from' => 5100.01, 'wage_to' => 5200.00, 'employer_amount' => 10.30, 'employee_amount' => 10.30],
            ['wage_from' => 5200.01, 'wage_to' => 5300.00, 'employer_amount' => 10.50, 'employee_amount' => 10.50],
            ['wage_from' => 5300.01, 'wage_to' => 5400.00, 'employer_amount' => 10.70, 'employee_amount' => 10.70],
            ['wage_from' => 5400.01, 'wage_to' => 5500.00, 'employer_amount' => 10.90, 'employee_amount' => 10.90],
            ['wage_from' => 5500.01, 'wage_to' => 5600.00, 'employer_amount' => 11.10, 'employee_amount' => 11.10],
            ['wage_from' => 5600.01, 'wage_to' => 5700.00, 'employer_amount' => 11.30, 'employee_amount' => 11.30],
            ['wage_from' => 5700.01, 'wage_to' => 5800.00, 'employer_amount' => 11.50, 'employee_amount' => 11.50],
            ['wage_from' => 5800.01, 'wage_to' => 5900.00, 'employer_amount' => 11.70, 'employee_amount' => 11.70],
            ['wage_from' => 5900.01, 'wage_to' => 6000.00, 'employer_amount' => 11.90, 'employee_amount' => 11.90],
        ];

        if (EisContributionTier::count() === 0) {
            EisContributionTier::insert($eisTiers);
        }

        echo '  SocsoEisSeeder: '.count($socsoTiers).' SOCSO + '.count($eisTiers)." EIS tiers\n";
    }
}
