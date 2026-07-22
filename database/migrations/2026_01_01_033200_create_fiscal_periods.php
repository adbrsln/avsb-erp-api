<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->create('fiscal_periods', function ($table) {
            $table->id();
            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('type', ['month', 'quarter', 'year'])->default('month');
            $table->enum('status', ['open', 'closed', 'locked'])->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('opening_balance_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();
            $table->unique(['start_date', 'end_date']);
        });
    }

    public function down(Builder $schema): void
    {
        $schema->dropIfExists('fiscal_periods');
    }
};
