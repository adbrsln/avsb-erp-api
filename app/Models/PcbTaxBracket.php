<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PcbTaxBracket extends Model
{
    use HasFactory;

    protected $table = 'pcb_tax_brackets';

    protected $fillable = [
        'year', 'worker_category', 'min_chargeable', 'max_chargeable', 'rate',
    ];

    protected $casts = [
        'year' => 'integer',
        'min_chargeable' => 'float',
        'max_chargeable' => 'float',
        'rate' => 'float',
    ];
}
