<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->create('subcontractors', function ($table) {
            $table->id();
            $table->string('subcontractor_code', 20)->unique();
            $table->string('company_name', 200);
            $table->string('registration_no', 50)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->string('cidb_reg_no', 50)->nullable();
            $table->string('cidb_grade', 10)->nullable();
            $table->date('cidb_expiry')->nullable();
            $table->json('licenses')->nullable();
            $table->json('insurances')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->dropIfExists('subcontractors');
    }
};
