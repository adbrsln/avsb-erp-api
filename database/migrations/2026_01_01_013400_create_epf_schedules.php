<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epf_schedules', function (Blueprint $table) {
            $table->string('code', 10)->primary();
            $table->string('name', 100);
            $table->decimal('employer_rate', 5, 2)->nullable()->comment('Percentage for wages > RM5,000');
            $table->decimal('employee_rate', 5, 2)->nullable()->comment('Percentage for wages > RM5,000');
            $table->decimal('max_tier_wage', 10, 2)->default(5000.00);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $now = Carbon::now();
        Schema::getConnection()->table('epf_schedules')->insert([
            ['code' => 'A', 'name' => 'Standard (Citizen/PR < 60)', 'employer_rate' => 12.00, 'employee_rate' => 11.00, 'max_tier_wage' => 20000.00, 'description' => null, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'C', 'name' => 'Reduced (PR/Elected ≥ 60, Non-citizen)', 'employer_rate' => 6.00, 'employee_rate' => 5.50, 'max_tier_wage' => 20000.00, 'description' => null, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'D', 'name' => 'Citizen ≥ 60', 'employer_rate' => 4.00, 'employee_rate' => 0.00, 'max_tier_wage' => 20000.00, 'description' => null, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'FLAT', 'name' => 'Other Non-citizens (Flat 2%)', 'employer_rate' => 2.00, 'employee_rate' => 2.00, 'max_tier_wage' => 5000.00, 'description' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('epf_schedules');
    }
};
