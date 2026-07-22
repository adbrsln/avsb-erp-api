<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socso_contribution_tiers', function (Blueprint $table) {
            $table->id();
            $table->decimal('wage_from', 10, 2);
            $table->decimal('wage_to', 10, 2);
            $table->decimal('employer_amount', 10, 2);
            $table->decimal('employee_amount', 10, 2);
            $table->unique(['wage_from', 'wage_to']);
        });

        // Format: RM100 brackets from 0.01→1500.00 up to 7500.00
        $rows = [];
        $empAmount = 25.35;
        $eeAmount = 7.25;
        $prevWageTo = 0;
        $wageTo = 1500.00;
        $alt = 0;

        for ($i = 0; $i < 61; $i++) {
            $wageFrom = $i === 0 ? 0.01 : round($prevWageTo + 0.01, 2);
            $rows[] = [
                'wage_from' => $wageFrom,
                'wage_to' => $wageTo,
                'employer_amount' => round($empAmount, 2),
                'employee_amount' => round($eeAmount, 2),
            ];
            $prevWageTo = $wageTo;
            $wageTo = round($wageTo + 100.00, 2);
            $empAmount = round($empAmount + ($alt === 0 ? 1.80 : 1.70), 2);
            $eeAmount = round($eeAmount + 0.50, 2);
            $alt = 1 - $alt;
        }

        Schema::getConnection()->table('socso_contribution_tiers')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('socso_contribution_tiers');
    }
};
