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
                'prefix' => 'AVSB-',
                'pattern' => '{PREFIX}{YEAR}-{MONTH}-{SEQ:4}',
                'description' => 'Project filing ID',
            ],
            [
                'code' => 'invoice',
                'prefix' => 'AVSB-INV',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Tax invoice number',
            ],
            [
                'code' => 'contract',
                'prefix' => 'AVSB-CNT',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Contract reference number',
            ],
            [
                'code' => 'quote',
                'prefix' => 'AVSB-QTE',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Quotation reference number',
            ],
            [
                'code' => 'employee',
                'prefix' => 'AVSB-EMP',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Employee ID',
            ],
            [
                'code' => 'pay_run',
                'prefix' => 'AVSB-PR',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Pay run reference number',
            ],
            [
                'code' => 'journal',
                'prefix' => 'AVSB-JE',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Journal entry number',
            ],
            [
                'code' => 'claim',
                'prefix' => 'AVSB-CLM',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Expense claim reference',
            ],
            [
                'code' => 'leave',
                'prefix' => 'AVSB-LV',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Leave application reference',
            ],
            [
                'code' => 'bill',
                'prefix' => 'AVSB-BL',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Bill number',
            ],
            [
                'code' => 'project_claim',
                'prefix' => 'AVSB-PC',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Project claim number',
            ],
            [
                'code' => 'purchase_order',
                'prefix' => 'AVSB-PO',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Purchase order number',
            ],
            [
                'code' => 'vendor',
                'prefix' => 'AVSB-V',
                'pattern' => '{PREFIX}{SEQ:4}',
                'description' => 'Vendor code',
            ],
            [
                'code' => 'subcontractor',
                'prefix' => 'AVSB-SUB',
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
                'prefix' => 'AVSB-SBI',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Self-billed invoice number',
            ],
            [
                'code' => 'variation_order',
                'prefix' => 'AVSB-VO',
                'pattern' => '{PREFIX}{YEAR}{MONTH}{SEQ:4}',
                'description' => 'Variation order number',
            ],
            [
                'code' => 'asset',
                'prefix' => 'AVSB-ASST',
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
