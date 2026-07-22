<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_claim_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_claim_id')->constrained('project_claims')->cascadeOnDelete();
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

    public function down(): void
    {
        Schema::dropIfExists('project_claim_documents');
    }
};
