<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->create('rate_limits', function ($table) {
            $table->id();
            $table->string('ip_hash', 64);
            $table->string('endpoint', 255)->default('/');
            $table->unsignedInteger('count')->default(1);
            $table->unsignedInteger('window_start');
            $table->unique(['ip_hash', 'endpoint'], 'uk_ip_endpoint');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->dropIfExists('rate_limits');
    }
};
