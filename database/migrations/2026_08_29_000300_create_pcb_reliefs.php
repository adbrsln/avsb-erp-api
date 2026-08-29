<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pcb_reliefs', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year');
            $table->string('code', 40);
            $table->string('label');
            $table->decimal('annual_cap', 15, 2);
            $table->timestamps();

            $table->unique(['year', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pcb_reliefs');
    }
};
