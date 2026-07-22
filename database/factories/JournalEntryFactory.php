<?php

namespace Database\Factories;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'entry_number' => 'JE-'.fake()->unique()->numerify('######'),
            'entry_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'description' => fake()->randomElement([
                'Invoice payment received',
                'Supplier bill payment',
                'Monthly payroll journal',
                'Accrued revenue recognition',
                'Depreciation entry',
                'SST payment to LHDN',
                'Office expense reimbursement',
                'Contract retention release',
            ]),
            'reference_type' => fake()->randomElement(['invoice', 'bill', 'payment', 'payroll', 'adjustment']),
            'reference_id' => fake()->numberBetween(1, 1000),
            'status' => 'posted',
            'created_by' => User::factory(),
            'posted_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'posted_at' => null,
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (JournalEntry $entry) {
            $total = fake()->randomFloat(2, 1000, 100000);
            $debitAccount = ChartOfAccount::where('type', 'expense')->inRandomOrder()->first() ?? ChartOfAccount::factory()->create(['type' => 'expense']);
            $creditAccount = ChartOfAccount::where('type', 'liability')->inRandomOrder()->first() ?? ChartOfAccount::factory()->create(['type' => 'liability']);

            $entry->lines()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $debitAccount->id,
                'debit' => $total,
                'credit' => 0,
                'description' => $entry->description,
            ]);

            $entry->lines()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $creditAccount->id,
                'debit' => 0,
                'credit' => $total,
                'description' => $entry->description,
            ]);
        });
    }
}
