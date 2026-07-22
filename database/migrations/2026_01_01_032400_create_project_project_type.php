<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_project_type', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_type_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'project_type_id']);
        });

        // Migrate existing data
        $projects = Schema::getConnection()->table('projects')
            ->whereNotNull('project_type_id')
            ->get();

        foreach ($projects as $project) {
            Schema::getConnection()->table('project_project_type')->insert([
                'project_id' => $project->id,
                'project_type_id' => $project->project_type_id,
            ]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['project_type_id']);
            $table->dropColumn('project_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('project_type_id')->nullable()->constrained()->nullOnDelete();
        });

        // Restore project_type_id from pivot
        $pivotRows = Schema::getConnection()->table('project_project_type')->get();
        $processed = [];
        foreach ($pivotRows as $row) {
            if (! isset($processed[$row->project_id])) {
                Schema::getConnection()->table('projects')
                    ->where('id', $row->project_id)
                    ->update(['project_type_id' => $row->project_type_id]);
                $processed[$row->project_id] = true;
            }
        }

        Schema::dropIfExists('project_project_type');
    }
};
