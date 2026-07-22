<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $db = $schema->getConnection();
        $db->table('users')
            ->whereIn('role', ['hr', 'owner'])
            ->update(['role' => 'admin']);
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        // Irreversible — no way to know which admin was originally hr vs owner
    }
};
