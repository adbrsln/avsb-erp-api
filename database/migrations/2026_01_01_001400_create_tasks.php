<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('tasks', function ($table) {
            $table->id();
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('phase_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('todo');
            $table->string('assigned_to')->nullable();
            $table->string('priority')->default('medium');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->datetime('actual_start')->nullable();
            $table->datetime('actual_end')->nullable();
            $table->string('pause_reason')->nullable();
            $table->datetime('paused_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->dropIfExists('tasks');
    }
};
