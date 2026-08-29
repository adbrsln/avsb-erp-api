<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pcb_tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year');
            $table->string('worker_category', 30)->default('pemastautin');
            $table->decimal('min_chargeable', 15, 2);
            $table->decimal('max_chargeable', 15, 2)->nullable();
            $table->decimal('rate', 5, 2);
            $table->timestamps();

            $table->unique(['year', 'worker_category', 'min_chargeable']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pcb_tax_brackets');
    }
};
