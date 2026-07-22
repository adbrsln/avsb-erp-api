<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('phases', function ($table) {
            $table->foreignId('started_by')->nullable()->constrained('staff_profiles')->nullOnDelete()->after('status');
            $table->datetime('started_at')->nullable()->after('started_by');
            $table->foreignId('completed_by')->nullable()->constrained('staff_profiles')->nullOnDelete()->after('started_at');
            $table->datetime('completed_at')->nullable()->after('completed_by');
            $table->text('completion_remarks')->nullable()->after('completed_at');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('phases', function ($table) {
            $table->dropForeign(['started_by']);
            $table->dropForeign(['completed_by']);
            $table->dropColumn(['started_by', 'started_at', 'completed_by', 'completed_at', 'completion_remarks']);
        });
    }
};
