<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->create('project_types', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('color', 7)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        $schema->table('projects', function ($table) {
            $table->foreignId('project_type_id')->nullable()->constrained('project_types')->nullOnDelete();
        });
    }

    public function down(Builder $schema)
    {
        $schema->table('projects', function ($table) {
            $table->dropForeign(['project_type_id']);
            $table->dropColumn('project_type_id');
        });
        $schema->dropIfExists('project_types');
    }
};
