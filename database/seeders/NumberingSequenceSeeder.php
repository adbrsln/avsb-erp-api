<?php

namespace Database\Seeders;

use App\Models\NumberingSequence;

class NumberingSequenceSeeder
{
    public function run(): void
    {
        $sequences = [
            [
                'code' => 'project',
                'prefix' => 'AV-',
                'pattern' => '{PREFIX}{YEAR}-{MONTH}-{SEQ:4}',
                'description' => 'Project filing ID — AV-{client_code}-{YYMM}-{SEQ} when client linked',
            ],
            [
                'code' => 'invoice',
                'prefix' => 'AV-INV',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Tax invoice number',
            ],
            [
                'code' => 'contract',
                'prefix' => 'AV-CNT',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Contract reference number',
            ],
            [
                'code' => 'quote',
                'prefix' => 'AV-QTE',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Quotation reference number',
            ],
            [
                'code' => 'employee',
                'prefix' => 'AV-EMP',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Employee ID',
            ],
            [
                'code' => 'pay_run',
                'prefix' => 'AV-PR',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Pay run reference number',
            ],
            [
                'code' => 'journal',
                'prefix' => 'AV-JE',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Journal entry number',
            ],
            [
                'code' => 'claim',
                'prefix' => 'AV-CLM',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Expense claim reference',
            ],
            [
                'code' => 'leave',
                'prefix' => 'AV-LV',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Leave application reference',
            ],
            [
                'code' => 'bill',
                'prefix' => 'AV-BL',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Bill number',
            ],
            [
                'code' => 'project_claim',
                'prefix' => 'AV-PC',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Project claim number',
            ],
            [
                'code' => 'purchase_order',
                'prefix' => 'AV-PO',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Purchase order number',
            ],
            [
                'code' => 'vendor',
                'prefix' => 'AV-V',
                'pattern' => '{PREFIX}{SEQ:4}',
                'description' => 'Vendor code',
            ],
            [
                'code' => 'subcontractor',
                'prefix' => 'AV-SUB',
                'pattern' => '{PREFIX}{SEQ:4}',
                'description' => 'Subcontractor code',
            ],
            [
                'code' => 'client',
                'prefix' => 'CLT-',
                'pattern' => '{PREFIX}{SEQ:4}',
                'description' => 'Client code',
            ],
            [
                'code' => 'self_billed_invoice',
                'prefix' => 'AV-SBI',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Self-billed invoice number',
            ],
            [
                'code' => 'variation_order',
                'prefix' => 'AV-VO',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Variation order number',
            ],
            [
                'code' => 'asset',
                'prefix' => 'AV-ASST',
                'pattern' => '{PREFIX}-{SEQ:4}',
                'description' => 'Asset QR code',
            ],
        ];

        foreach ($sequences as $seq) {
            NumberingSequence::firstOrCreate(
                ['code' => $seq['code']],
                $seq
            );
        }
    }
}
