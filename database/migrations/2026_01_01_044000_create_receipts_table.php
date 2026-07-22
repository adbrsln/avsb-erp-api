<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 50)->unique();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('invoice_payment_id')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('invoice_payment_id')->references('id')->on('invoice_payments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
