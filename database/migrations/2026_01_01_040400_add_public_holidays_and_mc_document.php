<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('public_holidays')) {
            Schema::create('public_holidays', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->date('date');
                $table->smallInteger('year')->nullable();
                $table->boolean('is_recurring')->default(false);
                $table->timestamps();
                $table->unique(['date', 'year']);
            });
        }

        if (! Schema::hasColumn('leave_applications', 'mc_document_path')) {
            Schema::table('leave_applications', function (Blueprint $table) {
                $table->string('mc_document_path', 255)->nullable()->after('reason');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('public_holidays');
        if (Schema::hasColumn('leave_applications', 'mc_document_path')) {
            Schema::table('leave_applications', function (Blueprint $table) {
                $table->dropColumn('mc_document_path');
            });
        }
    }
};
