<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socso_24h_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('category', 10);
            $table->integer('phase')->default(1);
            $table->decimal('wage_from', 10, 2);
            $table->decimal('wage_to', 10, 2)->nullable();
            $table->decimal('employee_amount', 10, 2);
            $table->index(['category', 'phase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socso_24h_tiers');
    }
};
