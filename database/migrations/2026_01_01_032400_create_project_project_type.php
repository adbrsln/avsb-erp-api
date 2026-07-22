<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->create('project_project_type', function ($table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_type_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'project_type_id']);
        });

        // Migrate existing data
        $projects = $schema->getConnection()->table('projects')
            ->whereNotNull('project_type_id')
            ->get();

        foreach ($projects as $project) {
            $schema->getConnection()->table('project_project_type')->insert([
                'project_id' => $project->id,
                'project_type_id' => $project->project_type_id,
            ]);
        }

        $schema->table('projects', function ($table) {
            $table->dropForeign(['project_type_id']);
            $table->dropColumn('project_type_id');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('projects', function ($table) {
            $table->foreignId('project_type_id')->nullable()->constrained()->nullOnDelete();
        });

        // Restore project_type_id from pivot
        $pivotRows = $schema->getConnection()->table('project_project_type')->get();
        $processed = [];
        foreach ($pivotRows as $row) {
            if (!isset($processed[$row->project_id])) {
                $schema->getConnection()->table('projects')
                    ->where('id', $row->project_id)
                    ->update(['project_type_id' => $row->project_type_id]);
                $processed[$row->project_id] = true;
            }
        }

        $schema->dropIfExists('project_project_type');
    }
};
