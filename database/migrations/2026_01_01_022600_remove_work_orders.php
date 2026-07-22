<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $db = Schema::getConnection();

        // Detach work orders from tasks before dropping
        $db->table('tasks')->whereNotNull('work_order_id')->update(['work_order_id' => null]);

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['work_order_id']);
            $table->dropColumn('work_order_id');
        });

        Schema::dropIfExists('work_orders');
    }

    public function down(): void
    {
        // Irreversible
    }
};
