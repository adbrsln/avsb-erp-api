<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcontractor_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_subcontractor_id')->constrained('project_subcontractors')->cascadeOnDelete();
            $table->string('claim_number', 50)->unique();
            $table->date('claim_date');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('work_done_pct', 5, 2)->nullable();
            $table->decimal('cumulative_pct', 5, 2)->nullable();
            $table->decimal('claimed_amount', 14, 2)->default(0);
            $table->decimal('retention_deducted', 14, 2)->default(0);
            $table->decimal('net_payable', 14, 2)->default(0);
            $table->decimal('previous_paid', 14, 2)->default(0);
            $table->decimal('current_due', 14, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('staff_profiles');
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('staff_profiles');
            $table->dateTime('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('staff_profiles');
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcontractor_claims');
    }
};
