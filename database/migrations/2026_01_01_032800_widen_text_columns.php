<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
        Schema::table('claim_items', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
        Schema::table('billing_milestones', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
        Schema::table('numbering_sequences', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
        Schema::table('checklist_results', function (Blueprint $table) {
            $table->renameColumn('notes', 'remarks');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
        });
        Schema::table('claim_items', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
        });
        Schema::table('billing_milestones', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
        });
        Schema::table('numbering_sequences', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
        });
        Schema::table('checklist_results', function (Blueprint $table) {
            $table->renameColumn('remarks', 'notes');
        });
    }
};
