<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->create('numbering_sequences', function ($table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('prefix', 50);
            $table->string('pattern', 100);
            $table->integer('last_sequence')->default(0);
            $table->string('last_year_month', 6)->default('');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema)
    {
        $schema->dropIfExists('numbering_sequences');
    }
};
