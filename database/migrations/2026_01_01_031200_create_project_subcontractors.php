<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->create('project_subcontractors', function ($table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('subcontractor_id')->constrained('subcontractors');
            $table->text('scope_of_work')->nullable();
            $table->decimal('contract_value', 14, 2)->default(0);
            $table->decimal('retention_pct', 5, 2)->default(5.00);
            $table->decimal('retention_amount', 14, 2)->default(0);
            $table->decimal('retention_released_at_cc', 14, 2)->default(0);
            $table->decimal('retention_released_at_dlp', 14, 2)->default(0);
            $table->date('dlp_end_date')->nullable();
            $table->date('cc_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('assigned_by')->nullable()->constrained('staff_profiles');
            $table->timestamps();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->dropIfExists('project_subcontractors');
    }
};
