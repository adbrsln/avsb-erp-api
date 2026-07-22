<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('leave_groups', function ($table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $schema->create('leave_group_entitlements', function ($table) {
            $table->id();
            $table->foreignId('leave_group_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('label');
            $table->decimal('days_entitled', 5, 1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        $schema->create('staff_leave_balances', function ($table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->string('type');
            $table->integer('year');
            $table->decimal('entitled', 5, 1);
            $table->decimal('used', 5, 1)->default(0);
            $table->decimal('adjusted', 5, 1)->default(0);
            $table->decimal('balance', 5, 1)->default(0);
            $table->unique(['staff_id', 'type', 'year']);
            $table->timestamps();
        });

        $schema->table('staff_profiles', function ($table) {
            $table->foreignId('leave_group_id')->nullable()->constrained('leave_groups')->nullOnDelete();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('staff_profiles', function ($table) {
            $table->dropForeign(['leave_group_id']);
            $table->dropColumn('leave_group_id');
        });
        $schema->dropIfExists('staff_leave_balances');
        $schema->dropIfExists('leave_group_entitlements');
        $schema->dropIfExists('leave_groups');
    }
};
