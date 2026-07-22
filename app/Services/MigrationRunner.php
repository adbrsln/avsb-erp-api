<?php

namespace App\Services;

use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Facades\DB;

class MigrationRunner
{
    private SchemaBuilder $schema;

    private array $applied;

    private int $batch;

    public function __construct(SchemaBuilder $schema)
    {
        $this->schema = $schema;
        $this->ensureTrackingTable();
        $this->batch = (int) DB::table('schema_migrations')->max('batch') + 1;
        $this->applied = DB::table('schema_migrations')->pluck('migration')->all();
    }

    public function runUp(): array
    {
        $results = [];
        $files = $this->getMigrationFiles();

        foreach ($files as $file) {
            $name = basename($file);

            if (in_array($name, $this->applied)) {
                echo "Skipped: {$name} (already applied)\n";
                $results[] = ['file' => $name, 'status' => 'skipped'];

                continue;
            }

            $status = $this->execute($file, $name);
            $results[] = ['file' => $name, 'status' => $status];

            if ($status === 'error') {
                exit(1);
            }
        }

        return $results;
    }

    public function rollbackLastBatch(): array
    {
        $maxBatch = (int) DB::table('schema_migrations')->max('batch');
        if ($maxBatch === 0) {
            echo "Nothing to roll back.\n";

            return [];
        }

        $toRollback = DB::table('schema_migrations')
            ->where('batch', $maxBatch)
            ->orderByDesc('migration')
            ->pluck('migration')
            ->all();

        $results = [];
        foreach ($toRollback as $name) {
            $file = __DIR__.'/../../database/migrations/'.$name;
            if (! file_exists($file)) {
                echo "Skip: {$name} — file not found\n";
                DB::table('schema_migrations')->where('migration', $name)->delete();

                continue;
            }

            $migration = require $file;
            echo "Rolling back: {$name}\n";
            try {
                $migration->down($this->schema);
                DB::table('schema_migrations')->where('migration', $name)->delete();
                $results[] = ['file' => $name, 'status' => 'rolled_back'];
            } catch (\Exception $e) {
                echo "Error: {$name} — {$e->getMessage()}\n";
                $results[] = ['file' => $name, 'status' => 'error', 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    public function rollbackAll(): array
    {
        $all = DB::table('schema_migrations')
            ->orderByDesc('batch')
            ->orderByDesc('migration')
            ->pluck('migration')
            ->all();

        if (empty($all)) {
            echo "Nothing to roll back.\n";

            return [];
        }

        $results = [];
        foreach ($all as $name) {
            $file = __DIR__.'/../../database/migrations/'.$name;
            if (! file_exists($file)) {
                echo "Skip: {$name} — file not found\n";
                DB::table('schema_migrations')->where('migration', $name)->delete();

                continue;
            }

            $migration = require $file;
            echo "Rolling back: {$name}\n";
            try {
                $migration->down($this->schema);
                DB::table('schema_migrations')->where('migration', $name)->delete();
                $results[] = ['file' => $name, 'status' => 'rolled_back'];
            } catch (\Exception $e) {
                echo "Error: {$name} — {$e->getMessage()}\n";
                $results[] = ['file' => $name, 'status' => 'error', 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    private function execute(string $file, string $name): string
    {
        $migration = require $file;

        if (! $migration || ! method_exists($migration, 'up')) {
            echo "Error: {$name} — invalid migration class\n";

            return 'error';
        }

        try {
            $migration->up($this->schema);
            DB::table('schema_migrations')->insert([
                'migration' => $name,
                'batch' => $this->batch,
            ]);
            echo "Applied: {$name}\n";

            return 'applied';
        } catch (\Exception $e) {
            $msg = $e->getMessage();

            if (
                str_contains($msg, 'already exists') ||
                str_contains($msg, 'Duplicate column name') ||
                str_contains($msg, 'Duplicate entry') ||
                str_contains($msg, 'Base table or view already exists')
            ) {
                DB::table('schema_migrations')->insert([
                    'migration' => $name,
                    'batch' => $this->batch,
                ]);
                echo "Skipped: {$name} (already applied)\n";

                return 'skipped';
            }

            echo "Error: {$name} — {$msg}\n";

            return 'error';
        }
    }

    private function ensureTrackingTable(): void
    {
        if (! $this->schema->hasTable('schema_migrations')) {
            $this->schema->create('schema_migrations', function ($table) {
                $table->string('migration')->primary();
                $table->integer('batch');
                $table->timestamp('executed_at')->useCurrent();
            });
        }
    }

    private function getMigrationFiles(): array
    {
        $files = glob(__DIR__.'/../../database/migrations/*.php');
        sort($files);

        return $files;
    }
}
