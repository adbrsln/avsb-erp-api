<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('phases')) {
            Schema::rename('phases', 'project_phases');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_phases')) {
            Schema::rename('project_phases', 'phases');
        }
    }
};
