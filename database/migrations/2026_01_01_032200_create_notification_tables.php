<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_templates')) {
            Schema::create('notification_templates', function (Blueprint $table) {
                $table->id();
                $table->string('event_type', 100)->unique();
                $table->string('category', 50)->default('other');
                $table->string('subject_template', 255);
                $table->text('body_template');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('notification_queue')) {
            Schema::create('notification_queue', function (Blueprint $table) {
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

        if (! Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
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

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_queue');
        Schema::dropIfExists('notification_templates');
    }
};
