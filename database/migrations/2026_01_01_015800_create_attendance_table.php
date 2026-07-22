<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->create('attendance', function ($table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->date('date');
            $table->datetime('clock_in');
            $table->datetime('clock_out')->nullable();
            $table->decimal('total_hours', 5, 2)->default(0);
            $table->string('latitude', 20)->nullable();
            $table->string('longitude', 20)->nullable();
            $table->string('clock_in_ip', 45)->nullable();
            $table->string('clock_out_ip', 45)->nullable();
            $table->string('status', 20)->default('present');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['staff_id', 'date']);
        });
    }

    public function down(Builder $schema)
    {
        $schema->dropIfExists('attendance');
    }
};
