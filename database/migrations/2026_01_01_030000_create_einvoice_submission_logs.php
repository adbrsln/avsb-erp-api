<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('einvoice_submission_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 50);
            $table->unsignedBigInteger('model_id');
            $table->string('action', 30);
            $table->longText('request_payload')->nullable();
            $table->longText('response_payload')->nullable();
            $table->integer('http_status')->nullable();
            $table->boolean('success')->default(false);
            $table->integer('duration_ms')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('einvoice_submission_logs');
    }
};
