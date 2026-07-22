<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('assets', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('asset_type', 100);
            $table->string('make', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->year('year')->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('registration_number', 100)->nullable();
            $table->json('specifications')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 12, 2)->default(0);
            $table->decimal('current_value', 12, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->string('condition', 20)->default('good');
            $table->date('warranty_expiry')->nullable();
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->string('location', 255)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('staff_profiles')->nullOnDelete();
            $table->string('purchase_order_ref', 100)->nullable();
            $table->string('bill_ref', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('staff_profiles')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->dropIfExists('assets');
    }
};
