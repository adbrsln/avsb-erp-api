<?php

return new class
{
    public function up($schema)
    {
        $schema->table('projects', function ($table) {
            $table->softDeletes();
        });
        $schema->table('staff_profiles', function ($table) {
            $table->softDeletes();
        });
        $schema->table('quotations', function ($table) {
            $table->softDeletes();
        });
        $schema->table('contracts', function ($table) {
            $table->softDeletes();
        });
        $schema->table('invoices', function ($table) {
            $table->softDeletes();
        });
        $schema->table('leave_applications', function ($table) {
            $table->softDeletes();
        });
        $schema->table('claims', function ($table) {
            $table->softDeletes();
        });
        $schema->table('timecards', function ($table) {
            $table->softDeletes();
        });
        $schema->table('pay_runs', function ($table) {
            $table->softDeletes();
        });
    }

    public function down($schema)
    {
        $schema->table('projects', function ($table) {
            $table->dropSoftDeletes();
        });
        $schema->table('staff_profiles', function ($table) {
            $table->dropSoftDeletes();
        });
        $schema->table('quotations', function ($table) {
            $table->dropSoftDeletes();
        });
        $schema->table('contracts', function ($table) {
            $table->dropSoftDeletes();
        });
        $schema->table('invoices', function ($table) {
            $table->dropSoftDeletes();
        });
        $schema->table('leave_applications', function ($table) {
            $table->dropSoftDeletes();
        });
        $schema->table('claims', function ($table) {
            $table->dropSoftDeletes();
        });
        $schema->table('timecards', function ($table) {
            $table->dropSoftDeletes();
        });
        $schema->table('pay_runs', function ($table) {
            $table->dropSoftDeletes();
        });
    }
};
