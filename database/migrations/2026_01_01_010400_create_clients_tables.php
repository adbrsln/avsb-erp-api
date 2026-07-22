<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('clients', function ($table) {
            $table->id();
            $table->string('client_code', 50)->nullable()->unique();
            $table->string('company_name');
            $table->string('registration_no', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('address')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('client_pics', function ($table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('job_title', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->table('projects', function ($table) {
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete()->after('client');
            $table->foreignId('client_pic_id')->nullable()->constrained('client_pics')->nullOnDelete()->after('client_id');
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->table('projects', function ($table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['client_pic_id']);
            $table->dropColumn(['client_id', 'client_pic_id']);
        });
        $schema->dropIfExists('client_pics');
        $schema->dropIfExists('clients');
    }
};
