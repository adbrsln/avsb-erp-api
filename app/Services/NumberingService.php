<?php

namespace App\Services;

use App\Models\NumberingSequence;

class NumberingService
{
    public function generate(string $code): string
    {
        $seq = NumberingSequence::firstOrCreate(
            ['code' => $code],
            [
                'prefix' => $this->defaultPrefix($code),
                'pattern' => '{PREFIX}{YEAR}-{MONTH}-{SEQ:4}',
                'last_sequence' => 0,
                'last_year_month' => '',
                'description' => 'Auto-created for '.$code,
            ]
        );
        $ym = date('ym');

        if ($seq->last_year_month !== $ym) {
            $seq->last_sequence = 0;
            $seq->last_year_month = $ym;
            $seq->save();
        }

        $seq->increment('last_sequence');
        $num = $seq->last_sequence;

        $replacements = [
            '{PREFIX}' => $seq->prefix,
            '{YEAR}' => date('y'),
            '{MONTH}' => date('m'),
        ];

        $pattern = $seq->pattern;
        if (preg_match('/\{SEQ:(\d+)\}/', $pattern, $m)) {
            $pattern = str_replace($m[0], str_pad((string) $num, (int) $m[1], '0', STR_PAD_LEFT), $pattern);
        }

        return str_replace(array_keys($replacements), array_values($replacements), $pattern);
    }

    private function defaultPrefix(string $code): string
    {
        return match ($code) {
            'vendor' => 'V-',
            'purchase_order' => 'PO-',
            'bill' => 'B-',
            'journal' => 'JE-',
            default => strtoupper(substr($code, 0, 3)).'-',
        };
    }
}
