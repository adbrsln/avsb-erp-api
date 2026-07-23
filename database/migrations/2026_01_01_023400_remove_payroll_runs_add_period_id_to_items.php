<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $db = Schema::getConnection();

        // 1. Add month/year to periods
        if (! Schema::hasColumn('payroll_periods', 'month')) {
            Schema::table('payroll_periods', function (Blueprint $table) {
                $table->tinyInteger('month')->unsigned()->nullable()->after('code');
                $table->smallInteger('year')->unsigned()->nullable()->after('month');
            });
        }

        $driver = $db->getDriverName();
        if ($driver === 'sqlite') {
            $db->statement("UPDATE payroll_periods SET month = CAST(strftime('%m', start_date) AS INTEGER), year = CAST(strftime('%Y', start_date) AS INTEGER) WHERE month IS NULL");
        } else {
            $db->statement('UPDATE payroll_periods SET month = MONTH(start_date), year = YEAR(start_date) WHERE month IS NULL');
        }

        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->tinyInteger('month')->unsigned()->nullable(false)->change();
            $table->smallInteger('year')->unsigned()->nullable(false)->change();
        });

        // 2. Add period_id to payroll_run_items
        if (! Schema::hasColumn('payroll_run_items', 'period_id')) {
            Schema::table('payroll_run_items', function (Blueprint $table) {
                $table->unsignedBigInteger('period_id')->nullable()->after('id');
            });
        }

        $driver = $db->getDriverName();
        if ($driver === 'sqlite') {
            $db->statement('UPDATE payroll_run_items SET period_id = (SELECT r.period_id FROM payroll_runs r WHERE r.id = payroll_run_items.payroll_run_id) WHERE period_id IS NULL');
        } else {
            $db->statement('UPDATE payroll_run_items i JOIN payroll_runs r ON r.id = i.payroll_run_id SET i.period_id = r.period_id WHERE i.period_id IS NULL');
        }

        // 3. Remove duplicates — keep the best item per (period_id, employee_id)
        $duplicates = $db->select('
            SELECT period_id, employee_id, COUNT(*) as cnt
            FROM payroll_run_items
            WHERE period_id IS NOT NULL
            GROUP BY period_id, employee_id
            HAVING cnt > 1
        ');

        foreach ($duplicates as $dup) {
            $items = $db->select('
                SELECT id, paid, confirmed, payroll_run_id
                FROM payroll_run_items
                WHERE period_id = ? AND employee_id = ?
                ORDER BY
                    CASE WHEN paid = 1 THEN 3 WHEN confirmed = 1 THEN 2 ELSE 1 END DESC,
                    id DESC
            ', [$dup->period_id, $dup->employee_id]);

            $keep = array_shift($items); // first = best
            foreach ($items as $remove) {
                $db->statement('DELETE FROM payroll_adjustments WHERE payroll_run_item_id = ?', [$remove->id]);
                $db->statement('UPDATE attendance SET payroll_run_item_id = NULL WHERE payroll_run_item_id = ?', [$remove->id]);
                $db->statement('DELETE FROM payroll_run_items WHERE id = ?', [$remove->id]);
            }
        }

        // 4. Finalize period_id constraints
        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->unsignedBigInteger('period_id')->nullable(false)->change();
            $table->foreign('period_id')->references('id')->on('payroll_periods')->restrictOnDelete();
            $table->unique(['period_id', 'employee_id']);
        });

        // 5. Drop payroll_run_id
        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->dropForeign(['payroll_run_id']);
            $table->dropColumn('payroll_run_id');
        });

        // 6. Drop runs table
        Schema::dropIfExists('payroll_runs');
    }

    public function down(): void
    {
        $db = Schema::getConnection();

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('payroll_periods')->restrictOnDelete();
            $table->datetime('processed_at');
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('total_employees')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->unsignedBigInteger('payroll_run_id')->nullable()->after('id');
            $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->cascadeOnDelete();
        });

        $periods = $db->select('SELECT DISTINCT period_id FROM payroll_run_items WHERE period_id IS NOT NULL');
        foreach ($periods as $row) {
            $db->insert("INSERT INTO payroll_runs (period_id, processed_at, status, total_employees, created_at, updated_at) VALUES (?, NOW(), 'completed', (SELECT COUNT(*) FROM payroll_run_items WHERE period_id = ?), NOW(), NOW())", [$row->period_id, $row->period_id]);
            $runId = $db->getPdo()->lastInsertId();
            $db->statement('UPDATE payroll_run_items SET payroll_run_id = ? WHERE period_id = ?', [$runId, $row->period_id]);
        }

        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->unsignedBigInteger('payroll_run_id')->nullable(false)->change();
            $table->dropForeign(['period_id']);
            $table->dropUnique(['period_id', 'employee_id']);
            $table->dropColumn('period_id');
        });

        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropColumn(['month', 'year']);
        });
    }
};
