<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        foreach (['quotations', 'contracts'] as $table) {
            $apply = function ($t) use ($schema, $table) {
                if (!$schema->hasColumn($table, 'buyer_tin')) {
                    $t->string('buyer_tin', 50)->nullable();
                }
                if (!$schema->hasColumn($table, 'buyer_reg_no')) {
                    $t->string('buyer_reg_no', 50)->nullable();
                }
                if (!$schema->hasColumn($table, 'buyer_sst_reg_no')) {
                    $t->string('buyer_sst_reg_no', 20)->nullable();
                }
                if (!$schema->hasColumn($table, 'buyer_contact')) {
                    $t->text('buyer_contact')->nullable();
                }
                if (!$schema->hasColumn($table, 'buyer_type')) {
                    $t->string('buyer_type', 20)->nullable();
                }
                if (!$schema->hasColumn($table, 'buyer_email')) {
                    $t->string('buyer_email', 255)->nullable();
                }
                if (!$schema->hasColumn($table, 'contact_phone')) {
                    $t->string('contact_phone', 50)->nullable();
                }
            };
            $schema->table($table, $apply);
        }
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        foreach (['quotations', 'contracts'] as $table) {
            $schema->table($table, function ($t) use ($schema, $table) {
                $t->dropColumn(['buyer_tin', 'buyer_reg_no', 'buyer_sst_reg_no', 'buyer_contact', 'buyer_type', 'buyer_email', 'contact_phone']);
            });
        }
    }
};
