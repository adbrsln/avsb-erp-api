<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        if (!$schema->hasColumn('attendance', 'flagged')) {
            $schema->table('attendance', function ($table) {
                $table->boolean('flagged')->default(false);
            });
        }
        if (!$schema->hasColumn('attendance', 'flagged_reason')) {
            $schema->table('attendance', function ($table) {
                $table->string('flagged_reason', 255)->nullable();
            });
        }
        if (!$schema->hasColumn('attendance', 'flagged_cleared_by')) {
            $schema->table('attendance', function ($table) {
                $table->foreignId('flagged_cleared_by')->nullable()->constrained('staff_profiles')->restrictOnDelete();
            });
        }
        if (!$schema->hasColumn('attendance', 'flagged_cleared_at')) {
            $schema->table('attendance', function ($table) {
                $table->datetime('flagged_cleared_at')->nullable();
            });
        }
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('attendance', function ($table) {
            $table->dropConstrainedForeignId('flagged_cleared_by');
            $table->dropColumn(['flagged', 'flagged_reason', 'flagged_cleared_at']);
        });
    }
};
