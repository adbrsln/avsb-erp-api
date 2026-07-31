<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'radius_meters')) {
                $table->unsignedInteger('radius_meters')->default(100);
            }
        });

        Schema::table('geofences', function (Blueprint $table) {
            if (! Schema::hasColumn('geofences', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable();
            }
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });

        // Backfill: create geofences for existing projects that have coordinates
        $projects = DB::table('projects')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNotIn('id', function ($query) {
                $query->select('project_id')->from('geofences')->whereNotNull('project_id');
            })
            ->get(['id', 'name', 'project_code', 'latitude', 'longitude', 'radius_meters', 'status']);

        foreach ($projects as $project) {
            DB::table('geofences')->insert([
                'name' => $project->name.($project->project_code ? ' ('.$project->project_code.')' : ''),
                'latitude' => $project->latitude,
                'longitude' => $project->longitude,
                'radius_meters' => $project->radius_meters ?? 100,
                'is_active' => $project->status === 'active',
                'project_id' => $project->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('geofences', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('radius_meters');
        });
    }
};
