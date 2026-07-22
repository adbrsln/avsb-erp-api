<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('project_code', 50)->nullable()->after('name');
            $table->decimal('budget_amount', 12, 2)->nullable()->after('status');
            $table->foreignId('project_manager_id')->nullable()->constrained('staff_profiles')->nullOnDelete()->after('budget_amount');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['project_manager_id']);
            $table->dropColumn(['project_code', 'budget_amount', 'project_manager_id']);
        });
    }
};
