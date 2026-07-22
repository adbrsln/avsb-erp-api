<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('einvoice_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('label', 50);
            $table->string('client_id', 255);
            $table->text('client_secret');
            $table->string('environment', 10)->default('sandbox');
            $table->boolean('is_active')->default(false);
            $table->string('cert_path', 255)->nullable();
            $table->string('key_path', 255)->nullable();
            $table->text('access_token')->nullable();
            $table->dateTime('token_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('einvoice_credentials');
    }
};
