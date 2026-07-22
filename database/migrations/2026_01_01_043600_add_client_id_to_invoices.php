<?php

use Illuminate\Database\Schema\Builder;

return new class
{
    public function up(Builder $schema): void
    {
        $schema->table('invoices', function ($table) use ($schema) {
            if (! $schema->hasColumn('invoices', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('client');
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            }
            if (! $schema->hasColumn('invoices', 'buyer_type')) {
                $table->string('buyer_type', 20)->nullable()->after('buyer_tin');
            }
            if (! $schema->hasColumn('invoices', 'buyer_email')) {
                $table->string('buyer_email', 255)->nullable()->after('buyer_contact');
            }
            if (! $schema->hasColumn('invoices', 'contact_phone')) {
                $table->string('contact_phone', 50)->nullable()->after('buyer_email');
            }
            if (! $schema->hasColumn('invoices', 'einvoice_status')) {
                $table->string('einvoice_status', 20)->nullable()->after('einvoice_type');
            }
        });
    }

    public function down(Builder $schema): void
    {
        $schema->table('invoices', function ($table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['client_id', 'buyer_type', 'buyer_email', 'contact_phone', 'einvoice_status']);
        });
    }
};
