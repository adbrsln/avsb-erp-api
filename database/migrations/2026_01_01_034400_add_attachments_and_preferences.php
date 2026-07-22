<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('notification_queue', 'attachments')) {
            Schema::table('notification_queue', function (Blueprint $table) {
                $table->json('attachments')->nullable()->after('context');
            });
        }

        if (! Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id');
                $table->string('event_type', 100);
                $table->boolean('email')->default(true);
                $table->boolean('push')->default(true);
                $table->boolean('in_app')->default(true);
                $table->timestamps();
                $table->unique(['user_id', 'event_type']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notification_queue', 'attachments')) {
            Schema::table('notification_queue', function (Blueprint $table) {
                $table->dropColumn('attachments');
            });
        }
        Schema::dropIfExists('notification_preferences');
    }
};
