<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->boolean('auto_closed')->default(false)->after('schedule_flag_reason');
            $table->string('auto_close_reason')->nullable()->after('auto_closed');
            $table->timestamp('auto_closed_at')->nullable()->after('auto_close_reason');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn(['auto_closed', 'auto_close_reason', 'auto_closed_at']);
        });
    }
};
