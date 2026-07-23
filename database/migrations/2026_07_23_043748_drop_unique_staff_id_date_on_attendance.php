<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // MySQL error 1553: can't drop unique index while FK uses the column
            DB::statement('ALTER TABLE `attendance` DROP FOREIGN KEY `attendance_staff_id_foreign`');
        }

        Schema::table('attendance', function (Blueprint $table) use ($driver) {
            $table->dropUnique(['staff_id', 'date']);
            // Add regular index to replace what unique provided for FK
            if ($driver === 'mysql') {
                $table->index('staff_id');
            }
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `attendance` ADD CONSTRAINT `attendance_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff_profiles` (`id`) ON DELETE CASCADE');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `attendance` DROP FOREIGN KEY `attendance_staff_id_foreign`');
        }

        Schema::table('attendance', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->dropIndex(['staff_id']);
            }
            $table->unique(['staff_id', 'date']);
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `attendance` ADD CONSTRAINT `attendance_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff_profiles` (`id`) ON DELETE CASCADE');
        }
    }
};
