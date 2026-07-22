<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->create('asset_licenses', function ($table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('license_type', 100);
            $table->string('license_number', 100)->nullable();
            $table->string('issuing_authority', 255)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date');
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->string('document_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(Builder $schema)
    {
        $schema->dropIfExists('asset_licenses');
    }
};
