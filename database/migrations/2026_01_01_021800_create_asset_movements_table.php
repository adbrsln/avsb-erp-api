<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->create('asset_movements', function ($table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('movement_type', 50);
            $table->string('from_location', 255)->nullable();
            $table->string('to_location', 255)->nullable();
            $table->foreignId('from_staff_id')->nullable()->constrained('staff_profiles')->nullOnDelete();
            $table->foreignId('to_staff_id')->nullable()->constrained('staff_profiles')->nullOnDelete();
            $table->date('movement_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('staff_profiles')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(Builder $schema)
    {
        $schema->dropIfExists('asset_movements');
    }
};
