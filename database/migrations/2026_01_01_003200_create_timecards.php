<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('timecards', function ($table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->decimal('hours_worked', 5, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->dropIfExists('timecards');
    }
};
