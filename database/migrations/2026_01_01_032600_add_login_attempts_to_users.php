<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        Capsule::schema()->table('users', function (Blueprint $table) {
            $table->integer('login_attempts')->default(0)->after('password');
            $table->timestamp('locked_until')->nullable()->after('login_attempts');
        });
    }

    public function down(): void
    {
        Capsule::schema()->table('users', function (Blueprint $table) {
            $table->dropColumn('login_attempts');
            $table->dropColumn('locked_until');
        });
    }
};
