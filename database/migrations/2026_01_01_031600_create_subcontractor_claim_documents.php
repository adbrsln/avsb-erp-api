<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->create('subcontractor_claim_documents', function ($table) {
            $table->id();
            $table->foreignId('subcontractor_claim_id')->constrained('subcontractor_claims')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('staff_profiles')->nullOnDelete();
            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);
            $table->string('file_path', 500);
            $table->string('mime_type', 100);
            $table->integer('file_size')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(Builder $schema): void
    {
        $schema->dropIfExists('subcontractor_claim_documents');
    }
};
