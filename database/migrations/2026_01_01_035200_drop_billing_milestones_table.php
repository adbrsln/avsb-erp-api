<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('billing_milestones');
    }

    public function down(): void
    {
        if (! Schema::hasTable('billing_milestones')) {
            Schema::create('billing_milestones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->string('description');
                $table->decimal('percentage', 5, 2);
                $table->decimal('amount', 12, 2);
                $table->date('due_date')->nullable();
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }
};
