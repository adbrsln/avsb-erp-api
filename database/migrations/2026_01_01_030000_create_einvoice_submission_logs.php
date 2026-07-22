<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->create('einvoice_submission_logs', function ($table) {
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

    public function down(Builder $schema): void
    {
        $schema->dropIfExists('einvoice_submission_logs');
    }
};
