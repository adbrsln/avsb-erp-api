<?php

namespace Database\Factories;

use App\Models\ProjectSubcontractor;
use App\Models\StaffProfile;
use App\Models\SubcontractorClaim;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubcontractorClaim>
 */
class SubcontractorClaimFactory extends Factory
{
    public function definition(): array
    {
        $claimedAmount = fake()->randomFloat(2, 10000, 500000);
        $retentionDeducted = round($claimedAmount * fake()->randomFloat(2, 0, 0.1), 2);

        return [
            'project_subcontractor_id' => ProjectSubcontractor::factory(),
            'claim_number' => 'SC-CLM-'.fake()->unique()->numerify('######'),
            'claim_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'period_start' => fn (array $attrs) => fake()->dateTimeBetween($attrs['claim_date'], '-30 days'),
            'period_end' => fake()->dateTimeBetween('-1 day', 'now'),
            'work_done_pct' => fake()->randomFloat(2, 10, 100),
            'cumulative_pct' => fake()->randomFloat(2, 10, 100),
            'claimed_amount' => $claimedAmount,
            'retention_deducted' => $retentionDeducted,
            'net_payable' => round($claimedAmount - $retentionDeducted, 2),
            'previous_paid' => fake()->randomFloat(2, 0, $claimedAmount),
            'current_due' => fn (array $attrs) => round($attrs['net_payable'] - $attrs['previous_paid'], 2),
            'status' => fake()->randomElement(['draft', 'submitted', 'verified', 'approved', 'rejected', 'paid']),
            'submitted_by' => StaffProfile::factory(),
            'submitted_at' => now(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
