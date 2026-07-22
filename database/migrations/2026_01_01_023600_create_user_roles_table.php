<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $db = Schema::getConnection();

        // 1. Create user_roles table
        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20);
            $table->primary(['user_id', 'role']);
            $table->timestamp('created_at')->useCurrent();
        });

        // 2. Migrate existing users.role to user_roles
        $users = $db->table('users')->get(['id', 'role']);
        foreach ($users as $user) {
            $role = $user->role;
            // Map legacy hr/owner to admin
            if (in_array($role, ['hr', 'owner'])) {
                $role = 'admin';
            }
            $db->table('user_roles')->insert([
                'user_id' => $user->id,
                'role' => $role,
            ]);
        }

        // 3. Drop the old column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        $db = Schema::getConnection();

        // Restore users.role with the first non-staff role (prefer admin > pm > staff)
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('staff')->after('password');
        });

        $users = $db->table('user_roles')
            ->select('user_id', 'role')
            ->orderByRaw("FIELD(role, 'admin', 'pm', 'finance', 'hr', 'staff')")
            ->get();

        $grouped = [];
        foreach ($users as $row) {
            if (! isset($grouped[$row->user_id])) {
                $grouped[$row->user_id] = $row->role;
            }
        }
        foreach ($grouped as $userId => $role) {
            $db->table('users')->where('id', $userId)->update(['role' => $role]);
        }

        Schema::dropIfExists('user_roles');
    }
};
