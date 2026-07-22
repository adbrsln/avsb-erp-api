<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class NumberingSequence extends Model
{
    use Auditable;

    protected $table = 'numbering_sequences';

    protected $fillable = [
        'code', 'prefix', 'pattern', 'last_sequence', 'last_year_month', 'description',
    ];

    protected $casts = [
        'last_sequence' => 'integer',
    ];
}
