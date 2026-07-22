<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        if (! $schema->hasTable('public_holidays')) {
            $schema->create('public_holidays', function ($table) {
                $table->id();
                $table->string('name', 100);
                $table->date('date');
                $table->smallInteger('year')->nullable();
                $table->boolean('is_recurring')->default(false);
                $table->timestamps();
                $table->unique(['date', 'year']);
            });
        }

        if (! $schema->hasColumn('leave_applications', 'mc_document_path')) {
            $schema->table('leave_applications', function ($table) {
                $table->string('mc_document_path', 255)->nullable()->after('reason');
            });
        }
    }

    public function down(Builder $schema): void
    {
        $schema->dropIfExists('public_holidays');
        if ($schema->hasColumn('leave_applications', 'mc_document_path')) {
            $schema->table('leave_applications', function ($table) {
                $table->dropColumn('mc_document_path');
            });
        }
    }
};
