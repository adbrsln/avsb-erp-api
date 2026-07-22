<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('payroll_adjustments', function ($table) {
            $table->dropForeign(['created_by']);
        });
        $schema->table('payroll_adjustments', function ($table) {
            $table->unsignedBigInteger('created_by')->nullable()->change();
        });
        $schema->table('payroll_adjustments', function ($table) {
            $table->foreign('created_by')->references('id')->on('staff_profiles')->nullOnDelete();
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('payroll_adjustments', function ($table) {
            $table->dropForeign(['created_by']);
        });
        $schema->table('payroll_adjustments', function ($table) {
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
        });
        $schema->table('payroll_adjustments', function ($table) {
            $table->foreign('created_by')->references('id')->on('staff_profiles')->restrictOnDelete();
        });
    }
};
