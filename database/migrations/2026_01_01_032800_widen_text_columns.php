<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        Capsule::schema()->table('quotation_items', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
        Capsule::schema()->table('claim_items', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
        Capsule::schema()->table('billing_milestones', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
        Capsule::schema()->table('numbering_sequences', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
        Capsule::schema()->table('checklist_results', function (Blueprint $table) {
            $table->renameColumn('notes', 'remarks');
        });
    }

    public function down(): void
    {
        Capsule::schema()->table('quotation_items', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
        });
        Capsule::schema()->table('claim_items', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
        });
        Capsule::schema()->table('billing_milestones', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
        });
        Capsule::schema()->table('numbering_sequences', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
        });
        Capsule::schema()->table('checklist_results', function (Blueprint $table) {
            $table->renameColumn('remarks', 'notes');
        });
    }
};
