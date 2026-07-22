<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_limits', function (Blueprint $table) {
            $table->id();
            $table->string('ip_hash', 64);
            $table->string('endpoint', 255)->default('/');
            $table->unsignedInteger('count')->default(1);
            $table->unsignedInteger('window_start');
            $table->unique(['ip_hash', 'endpoint'], 'uk_ip_endpoint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_limits');
    }
};
