<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        // ── Migrate staff_profiles.role to users.role ──
        $db = $schema->getConnection();
        $staffRows = $db->table('staff_profiles')->select('id', 'email', 'role')->get();

        foreach ($staffRows as $sp) {
            $authRole = match ($sp->role) {
                'pm' => 'pm',
                'admin' => 'admin',
                default => 'staff',
            };

            $existing = $db->table('users')->where('email', $sp->email)->first();

            if ($existing) {
                $db->table('users')->where('id', $existing->id)->update(['role' => $authRole]);
            } else {
                $db->table('users')->insert([
                    'name' => $sp->email,
                    'email' => $sp->email,
                    'password' => password_hash(bin2hex(random_bytes(4)), PASSWORD_BCRYPT),
                    'role' => $authRole,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // ── Drop role column from staff_profiles ──
        $schema->table('staff_profiles', function ($table) {
            $table->dropColumn('role');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('staff_profiles', function ($table) {
            $table->string('role', 50)->nullable()->after('employee_id');
        });
    }
};
