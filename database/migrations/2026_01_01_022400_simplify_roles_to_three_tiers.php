<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $db = Schema::getConnection();
        $db->table('users')
            ->whereIn('role', ['hr', 'owner'])
            ->update(['role' => 'admin']);
    }

    public function down(): void
    {
        // Irreversible — no way to know which admin was originally hr vs owner
    }
};
