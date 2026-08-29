<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->boolean('pcb_contributing')->default(true)->after('pcb_borne_by_employer');
        });
    }

    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropColumn('pcb_contributing');
        });
    }
};
