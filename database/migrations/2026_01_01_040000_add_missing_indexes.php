<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('projects', function ($table) {
            $table->index('status');
        });
        $schema->table('phases', function ($table) {
            $table->index(['project_id', 'status']);
        });
        $schema->table('tasks', function ($table) {
            $table->index(['phase_id', 'status']);
        });
        $schema->table('invoices', function ($table) {
            $table->index(['project_id', 'status']);
        });
        $schema->table('claims', function ($table) {
            $table->index('status');
        });
        $schema->table('leave_applications', function ($table) {
            $table->index('status');
        });
        $schema->table('notification_queue', function ($table) {
            $table->index('status');
        });
        $schema->table('journal_entries', function ($table) {
            $table->index(['reference_type', 'reference_id']);
        });
        $schema->table('inventory_transactions', function ($table) {
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(Builder $schema): void
    {
        $connection = $schema->getConnection();
        $schemaManager = $connection->getDoctrineSchemaManager();

        $tables = [
            'projects' => 'status',
            'claims' => 'status',
            'leave_applications' => 'status',
            'notification_queue' => 'status',
            'phases' => ['project_id', 'status'],
            'tasks' => ['phase_id', 'status'],
            'invoices' => ['project_id', 'status'],
            'journal_entries' => ['reference_type', 'reference_id'],
            'inventory_transactions' => ['reference_type', 'reference_id'],
        ];

        foreach ($tables as $tableName => $columns) {
            $indexName = $tableName.'_'.(is_array($columns) ? implode('_', $columns) : $columns).'_index';
            $existing = $schemaManager->listTableIndexes($tableName);
            if (array_key_exists($indexName, $existing)) {
                $connection->statement("ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`");
            }
        }
    }
};
