<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->create('push_subscriptions', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint');
            $table->string('auth_key');
            $table->string('p256dh_key');
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->unique('endpoint', 191);
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->dropIfExists('push_subscriptions');
    }
};
