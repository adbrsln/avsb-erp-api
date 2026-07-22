<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $db = $schema->getConnection();

        // Detach work orders from tasks before dropping
        $db->table('tasks')->whereNotNull('work_order_id')->update(['work_order_id' => null]);

        $schema->table('tasks', function ($table) {
            $table->dropForeign(['work_order_id']);
            $table->dropColumn('work_order_id');
        });

        $schema->dropIfExists('work_orders');
    }

    public function down(Builder $schema): void
    {
        // Irreversible
    }
};
