<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema)
    {
        $schema->create('vendors', function ($table) {
            $table->id();
            $table->string('vendor_code', 20)->unique();
            $table->string('company_name');
            $table->string('registration_no', 50)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('purchase_orders', function ($table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $schema->create('purchase_order_items', function ($table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->string('unit', 20)->default('Lot');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts');
        });

        $schema->create('bills', function ($table) {
            $table->id();
            $table->string('bill_number', 50)->unique();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vendor_bill_no', 100)->nullable();
            $table->date('bill_date');
            $table->date('due_date');
            $table->string('status', 20)->default('draft');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $schema->create('bill_items', function ($table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->string('unit', 20)->default('Lot');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts');
        });

        $schema->create('bill_payments', function ($table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->foreignId('debit_account_id')->constrained('chart_of_accounts');
            $table->foreignId('credit_account_id')->constrained('chart_of_accounts');
            $table->string('payment_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $schema->create('inventory_items', function ($table) {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name');
            $table->string('category', 100)->nullable();
            $table->string('unit', 20)->default('Lot');
            $table->decimal('stock_qty', 10, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('reorder_level', 10, 2)->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('inventory_transactions', function ($table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('type', 10); // in / out
            $table->decimal('qty', 10, 2);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->string('reference_type', 30)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(Builder $schema)
    {
        $tables = ['inventory_transactions', 'inventory_items', 'bill_payments', 'bill_items', 'bills',
            'purchase_order_items', 'purchase_orders', 'vendors'];
        foreach ($tables as $t) {
            $schema->dropIfExists($t);
        }
    }
};
