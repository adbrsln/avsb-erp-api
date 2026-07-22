<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('invoices', function ($table) {
            $table->char('uuid', 36)->unique()->nullable();
            $table->string('submission_status', 20)->nullable();
            $table->string('submission_uid', 100)->nullable();
            $table->string('long_id', 100)->nullable();
            $table->string('qr_code_url', 500)->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('last_submission_attempt')->nullable();
            $table->text('submission_error')->nullable();
            $table->string('einvoice_type', 20)->default('invoice');
            $table->date('supply_date')->nullable();
            $table->string('buyer_tin', 20)->nullable();
            $table->string('buyer_reg_no', 30)->nullable();
            $table->string('buyer_sst_reg_no', 20)->nullable();
            $table->string('buyer_contact', 100)->nullable();
            $table->string('seller_tin', 20)->nullable();
            $table->string('seller_sst_reg_no', 20)->nullable();
            $table->string('classification_code', 10)->nullable();
            $table->string('country', 2)->default('MY');
            $table->string('currency', 3)->default('MYR');
            $table->text('einvoice_notes')->nullable();
            $table->dateTime('einvoice_validated_at')->nullable();
            $table->longText('einvoice_xml')->nullable();
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('invoices', function ($table) {
            $table->dropColumn([
                'uuid', 'submission_status', 'submission_uid', 'long_id', 'qr_code_url',
                'submitted_at', 'last_submission_attempt', 'submission_error', 'einvoice_type',
                'supply_date', 'buyer_tin', 'buyer_reg_no', 'buyer_sst_reg_no', 'buyer_contact',
                'seller_tin', 'seller_sst_reg_no', 'classification_code', 'country', 'currency',
                'einvoice_notes', 'einvoice_validated_at', 'einvoice_xml',
            ]);
        });
    }
};
