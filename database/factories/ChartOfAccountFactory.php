<?php

namespace Database\Factories;

use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChartOfAccount>
 */
class ChartOfAccountFactory extends Factory
{
    private static array $accountTypes = [
        'asset' => ['1000-1999', 'Current Assets', 'Fixed Assets', 'Other Assets'],
        'liability' => ['2000-2999', 'Current Liabilities', 'Long Term Liabilities'],
        'equity' => ['3000-3999', 'Equity', 'Retained Earnings'],
        'income' => ['4000-4999', 'Revenue', 'Other Income'],
        'expense' => ['5000-5999', 'Operating Expenses', 'Administrative Expenses'],
    ];

    public function definition(): array
    {
        $type = fake()->randomElement(array_keys(self::$accountTypes));
        $options = self::$accountTypes[$type];
        $range = array_shift($options);
        $category = fake()->randomElement($options);
        $code = fake()->unique()->numerify(substr($range, 0, 1).'##0');

        return [
            'code' => $code,
            'name' => fake()->randomElement([
                'Cash & Bank',
                'Accounts Receivable',
                'Accounts Payable',
                'Revenue from Contracts',
                'Cost of Materials',
                'Office Supplies',
                'Staff Salaries',
                'SST Payable',
                'Retention Receivable',
                'Equipment Rental Income',
                'EPF Payable',
                'SOCSO Payable',
                'Accumulated Depreciation',
                'Retained Earnings',
            ]),
            'type' => $type,
            'category' => $category,
            'is_active' => true,
            'is_system' => false,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
