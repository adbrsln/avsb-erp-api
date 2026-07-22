<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leave_group_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_group_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('label');
            $table->decimal('days_entitled', 5, 1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('staff_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->string('type');
            $table->integer('year');
            $table->decimal('entitled', 5, 1);
            $table->decimal('used', 5, 1)->default(0);
            $table->decimal('adjusted', 5, 1)->default(0);
            $table->decimal('balance', 5, 1)->default(0);
            $table->unique(['staff_id', 'type', 'year']);
            $table->timestamps();
        });

        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->foreignId('leave_group_id')->nullable()->constrained('leave_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropForeign(['leave_group_id']);
            $table->dropColumn('leave_group_id');
        });
        Schema::dropIfExists('staff_leave_balances');
        Schema::dropIfExists('leave_group_entitlements');
        Schema::dropIfExists('leave_groups');
    }
};
