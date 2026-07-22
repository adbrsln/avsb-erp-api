<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('staff_profiles', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('role')->nullable();
            $table->string('job_title')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('basic_salary', 10, 2)->nullable();
            $table->string('epf_no')->nullable();
            $table->string('socso_no')->nullable();
            $table->date('date_joined')->nullable();
            $table->json('spouse')->nullable();
            $table->json('address')->nullable();
            $table->json('emergency_contact')->nullable();
            $table->json('dependent_children')->nullable();
            $table->timestamps();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->dropIfExists('staff_profiles');
    }
};
