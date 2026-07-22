<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eis_contribution_tiers', function (Blueprint $table) {
            $table->id();
            $table->decimal('wage_from', 10, 2);
            $table->decimal('wage_to', 10, 2);
            $table->decimal('employer_amount', 10, 2);
            $table->decimal('employee_amount', 10, 2);
            $table->unique(['wage_from', 'wage_to']);
        });

        // Format: RM100 brackets from 0.01→1500.00 up to 7500.00
        // Employer = Employee, +0.20 per bracket
        $rows = [];
        $eisAmount = 2.90;
        $prevWageTo = 0;
        $wageTo = 1500.00;

        for ($i = 0; $i < 61; $i++) {
            $wageFrom = $i === 0 ? 0.01 : round($prevWageTo + 0.01, 2);
            $rows[] = [
                'wage_from' => $wageFrom,
                'wage_to' => $wageTo,
                'employer_amount' => round($eisAmount, 2),
                'employee_amount' => round($eisAmount, 2),
            ];
            $prevWageTo = $wageTo;
            $wageTo = round($wageTo + 100.00, 2);
            $eisAmount = round($eisAmount + 0.20, 2);
        }

        Schema::getConnection()->table('eis_contribution_tiers')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('eis_contribution_tiers');
    }
};
