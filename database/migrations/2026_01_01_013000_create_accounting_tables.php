<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('type', 20); // asset, liability, equity, income, expense
            $table->string('category', 30)->nullable(); // current_asset, fixed_asset, current_liability, etc.
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 50)->unique();
            $table->date('entry_date');
            $table->text('description')->nullable();
            $table->string('reference_type', 30)->nullable(); // invoice, payment, payroll, claim, manual
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('status', 20)->default('draft'); // draft, posted
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->text('description')->nullable();
        });

        // Pre-seed Chart of Accounts for Malaysian construction company
        $now = Carbon::now();
        $accounts = [
            // === Assets (1000-1999) ===
            ['code' => '1101', 'name' => 'Petty Cash', 'type' => 'asset', 'category' => 'current_asset', 'description' => 'Petty cash in hand'],
            ['code' => '1102', 'name' => 'Maybank Current Account', 'type' => 'asset', 'category' => 'current_asset', 'description' => 'Maybank current account'],
            ['code' => '1103', 'name' => 'CIMB Current Account', 'type' => 'asset', 'category' => 'current_asset', 'description' => 'CIMB current account'],
            ['code' => '1104', 'name' => 'Trade Receivables - AR', 'type' => 'asset', 'category' => 'current_asset', 'description' => 'Amounts owing from customers'],
            ['code' => '1105', 'name' => 'Other Receivables', 'type' => 'asset', 'category' => 'current_asset', 'description' => 'Other amounts receivable'],
            ['code' => '1106', 'name' => 'Project Work In Progress', 'type' => 'asset', 'category' => 'current_asset', 'description' => 'Unbilled project costs'],
            ['code' => '1107', 'name' => 'SST Receivable', 'type' => 'asset', 'category' => 'current_asset', 'description' => 'SST input tax recoverable'],
            ['code' => '1108', 'name' => 'Deposits & Prepayments', 'type' => 'asset', 'category' => 'current_asset', 'description' => 'Deposits and prepaid expenses'],
            ['code' => '1201', 'name' => 'Office Equipment', 'type' => 'asset', 'category' => 'fixed_asset', 'description' => 'Office equipment at cost'],
            ['code' => '1202', 'name' => 'Accum. Depreciation - Office Equipment', 'type' => 'asset', 'category' => 'fixed_asset', 'is_system' => true],
            ['code' => '1203', 'name' => 'Machinery & Equipment', 'type' => 'asset', 'category' => 'fixed_asset', 'description' => 'Construction machinery at cost'],
            ['code' => '1204', 'name' => 'Accum. Depreciation - Machinery', 'type' => 'asset', 'category' => 'fixed_asset', 'is_system' => true],
            ['code' => '1205', 'name' => 'Motor Vehicles', 'type' => 'asset', 'category' => 'fixed_asset', 'description' => 'Company vehicles at cost'],
            ['code' => '1206', 'name' => 'Accum. Depreciation - Vehicles', 'type' => 'asset', 'category' => 'fixed_asset', 'is_system' => true],

            // === Liabilities (2000-2999) ===
            ['code' => '2101', 'name' => 'Trade Payables - AP', 'type' => 'liability', 'category' => 'current_liability', 'description' => 'Amounts owing to suppliers'],
            ['code' => '2102', 'name' => 'Other Payables', 'type' => 'liability', 'category' => 'current_liability', 'description' => 'Other amounts payable'],
            ['code' => '2103', 'name' => 'EPF Payable', 'type' => 'liability', 'category' => 'current_liability', 'description' => 'EPF contributions payable to KWSP'],
            ['code' => '2104', 'name' => 'SOCSO Payable', 'type' => 'liability', 'category' => 'current_liability', 'description' => 'SOCSO contributions payable to PERKESO'],
            ['code' => '2105', 'name' => 'EIS Payable', 'type' => 'liability', 'category' => 'current_liability', 'description' => 'EIS contributions payable'],
            ['code' => '2106', 'name' => 'PCB Payable', 'type' => 'liability', 'category' => 'current_liability', 'description' => 'PCB tax deductions payable to LHDN'],
            ['code' => '2107', 'name' => 'SST Payable', 'type' => 'liability', 'category' => 'current_liability', 'description' => 'SST output tax payable to customs'],
            ['code' => '2108', 'name' => 'Accrued Expenses', 'type' => 'liability', 'category' => 'current_liability', 'description' => 'Accrued expenses'],
            ['code' => '2109', 'name' => 'Progress Billings in Excess', 'type' => 'liability', 'category' => 'current_liability', 'description' => 'Billings in excess of project costs'],
            ['code' => '2201', 'name' => 'Bank Loan', 'type' => 'liability', 'category' => 'long_term_liability'],
            ['code' => '2202', 'name' => 'Hire Purchase Payable', 'type' => 'liability', 'category' => 'long_term_liability'],

            // === Equity (3000-3999) ===
            ['code' => '3101', 'name' => 'Share Capital', 'type' => 'equity', 'category' => 'equity', 'description' => 'Paid up share capital'],
            ['code' => '3102', 'name' => 'Retained Earnings', 'type' => 'equity', 'category' => 'equity', 'is_system' => true],
            ['code' => '3103', 'name' => 'Current Year Profit / Loss', 'type' => 'equity', 'category' => 'equity', 'is_system' => true],
            ['code' => '3104', 'name' => 'Drawings', 'type' => 'equity', 'category' => 'equity'],

            // === Revenue (4000-4999) ===
            ['code' => '4101', 'name' => 'Project Revenue', 'type' => 'income', 'category' => 'revenue', 'description' => 'Revenue from construction projects'],
            ['code' => '4102', 'name' => 'Service Revenue', 'type' => 'income', 'category' => 'revenue', 'description' => 'Revenue from services rendered'],
            ['code' => '4103', 'name' => 'Other Income', 'type' => 'income', 'category' => 'revenue'],

            // === Cost of Sales (5000-5999) ===
            ['code' => '5101', 'name' => 'Direct Materials', 'type' => 'expense', 'category' => 'cost_of_sales', 'description' => 'Direct materials used in projects'],
            ['code' => '5102', 'name' => 'Direct Labour', 'type' => 'expense', 'category' => 'cost_of_sales', 'description' => 'Direct labour costs'],
            ['code' => '5103', 'name' => 'Subcontractor Costs', 'type' => 'expense', 'category' => 'cost_of_sales'],
            ['code' => '5104', 'name' => 'Equipment Hire Costs', 'type' => 'expense', 'category' => 'cost_of_sales'],
            ['code' => '5105', 'name' => 'Project Overheads', 'type' => 'expense', 'category' => 'cost_of_sales'],

            // === Operating Expenses (6000-6999) ===
            ['code' => '6101', 'name' => 'Salaries & Wages', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6102', 'name' => 'EPF Contribution - Employer', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6103', 'name' => 'SOCSO Contribution - Employer', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6104', 'name' => 'EIS Contribution - Employer', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6105', 'name' => 'Directors Fees & Remuneration', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6201', 'name' => 'Office Rent', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6202', 'name' => 'Utilities', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6203', 'name' => 'Office Supplies', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6204', 'name' => 'Telephone & Internet', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6205', 'name' => 'Printing & Stationery', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6301', 'name' => 'Transportation Expenses', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6302', 'name' => 'Travel & Accommodation', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6303', 'name' => 'Staff Welfare', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6401', 'name' => 'Professional Fees', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6402', 'name' => 'Insurance', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6403', 'name' => 'Bank Charges', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6404', 'name' => 'License, Permits & Renewals', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6501', 'name' => 'Depreciation Expense', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6502', 'name' => 'Maintenance & Repairs', 'type' => 'expense', 'category' => 'operating_expense'],
            ['code' => '6503', 'name' => 'Advertising & Marketing', 'type' => 'expense', 'category' => 'operating_expense'],
        ];

        $rows = array_map(fn ($a) => array_merge([
            'description' => null,
            'is_system' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ], $a), $accounts);
        Schema::getConnection()->table('chart_of_accounts')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
    }
};
