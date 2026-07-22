<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->index('status');
        });
        Schema::table('phases', function (Blueprint $table) {
            $table->index(['project_id', 'status']);
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['phase_id', 'status']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['project_id', 'status']);
        });
        Schema::table('claims', function (Blueprint $table) {
            $table->index('status');
        });
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->index('status');
        });
        Schema::table('notification_queue', function (Blueprint $table) {
            $table->index('status');
        });
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->index(['reference_type', 'reference_id']);
        });
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        $connection = Schema::getConnection();
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
