<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_billed_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained('clients');
            $table->foreignId('project_id')->nullable()->constrained('projects');
            $table->date('date');
            $table->date('due_date');
            $table->date('supply_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('sst', 12, 2)->default(0);
            $table->decimal('retention', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->json('items')->nullable();
            $table->text('notes')->nullable();
            $table->char('uuid', 36)->unique()->nullable();
            $table->string('submission_status', 20)->nullable();
            $table->string('submission_uid', 100)->nullable();
            $table->string('long_id', 100)->nullable();
            $table->string('qr_code_url', 500)->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('last_submission_attempt')->nullable();
            $table->text('submission_error')->nullable();
            $table->longText('einvoice_xml')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('staff_profiles');
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('staff_profiles');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_billed_invoices');
    }
};
