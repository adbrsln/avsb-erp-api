<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->string('worker_category', 30)->default('pemastautin')->after('reported_to_lhdn');
            $table->boolean('spouse_working')->nullable()->after('marital_status');
            $table->boolean('spouse_disabled')->default(false)->after('spouse_working');
            $table->decimal('zakat_monthly', 10, 2)->default(0)->after('spouse_disabled');
            $table->json('children_tax')->nullable()->after('dependent_children');
        });
    }

    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropColumn(['worker_category', 'spouse_working', 'spouse_disabled', 'zakat_monthly', 'children_tax']);
        });
    }
};
