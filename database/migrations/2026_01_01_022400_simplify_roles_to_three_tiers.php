<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $db = $schema->getConnection();
        $db->table('users')
            ->whereIn('role', ['hr', 'owner'])
            ->update(['role' => 'admin']);
    }

    public function down(Builder $schema): void
    {
        // Irreversible — no way to know which admin was originally hr vs owner
    }
};
