<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        if (!$schema->hasTable('notification_templates')) {
            $schema->create('notification_templates', function ($table) {
                $table->id();
                $table->string('event_type', 100)->unique();
                $table->string('category', 50)->default('other');
                $table->string('subject_template', 255);
                $table->text('body_template');
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('notification_queue')) {
            $schema->create('notification_queue', function ($table) {
                $table->id();
                $table->string('event_type', 100)->index();
                $table->string('recipient_email', 255);
                $table->string('recipient_name', 255)->nullable();
                $table->string('subject', 255);
                $table->longText('body');
                $table->json('context')->nullable();
                $table->string('model_type', 100)->nullable();
                $table->bigInteger('model_id')->nullable();
                $table->enum('status', ['pending', 'processing', 'sent', 'failed'])->default('pending');
                $table->tinyInteger('attempts')->unsigned()->default(0);
                $table->tinyInteger('max_attempts')->unsigned()->default(3);
                $table->dateTime('processing_since')->nullable();
                $table->text('error')->nullable();
                $table->dateTime('scheduled_at')->nullable();
                $table->dateTime('sent_at')->nullable();
                $table->timestamps();
                $table->unique(['event_type', 'recipient_email', 'model_type', 'model_id'], 'notif_dedup');
            });
        }

        if (!$schema->hasTable('notification_logs')) {
            $schema->create('notification_logs', function ($table) {
                $table->id();
                $table->bigInteger('queue_id')->nullable();
                $table->string('event_type', 100)->index();
                $table->string('recipient_email', 255);
                $table->string('recipient_name', 255)->nullable();
                $table->string('subject', 255);
                $table->longText('body');
                $table->string('status', 20);
                $table->text('error')->nullable();
                $table->dateTime('sent_at');
            });
        }
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->dropIfExists('notification_logs');
        $schema->dropIfExists('notification_queue');
        $schema->dropIfExists('notification_templates');
    }
};
