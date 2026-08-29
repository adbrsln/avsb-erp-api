<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PcbRelief extends Model
{
    use HasFactory;

    protected $table = 'pcb_reliefs';

    protected $fillable = [
        'year', 'code', 'label', 'annual_cap',
    ];

    protected $casts = [
        'year' => 'integer',
        'annual_cap' => 'float',
    ];
}
