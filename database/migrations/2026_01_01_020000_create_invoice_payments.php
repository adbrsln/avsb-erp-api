<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->create('invoice_payments', function ($table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->foreignId('debit_account_id')->constrained('chart_of_accounts');
            $table->foreignId('credit_account_id')->constrained('chart_of_accounts');
            $table->string('payment_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Add partially_paid to invoice status comment (already a string, no migration needed)
    }

    public function down(Builder $schema)
    {
        $schema->dropIfExists('invoice_payments');
    }
};
