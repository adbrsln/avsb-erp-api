<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->timestamps();
        });

        // Backfill pivot from the existing single-owner FK
        DB::table('invoice_project')->insertUsing(
            ['invoice_id', 'project_id', 'created_at', 'updated_at'],
            DB::table('invoices')
                ->select('id', 'project_id', DB::raw('CURRENT_TIMESTAMP'), DB::raw('CURRENT_TIMESTAMP'))
                ->whereNotNull('project_id')
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_project');
    }
};
