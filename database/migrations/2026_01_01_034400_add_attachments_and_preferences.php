<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        if (!$schema->hasColumn('notification_queue', 'attachments')) {
            $schema->table('notification_queue', function ($table) {
                $table->json('attachments')->nullable()->after('context');
            });
        }

        if (!$schema->hasTable('notification_preferences')) {
            $schema->create('notification_preferences', function ($table) {
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

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        if ($schema->hasColumn('notification_queue', 'attachments')) {
            $schema->table('notification_queue', function ($table) {
                $table->dropColumn('attachments');
            });
        }
        $schema->dropIfExists('notification_preferences');
    }
};
