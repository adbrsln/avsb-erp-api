<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class TaxCode extends Model
{
    use Auditable;

    protected $table = 'tax_codes';

    protected $fillable = ['code', 'name', 'rate', 'is_active'];

    protected $casts = [
        'rate' => 'float',
        'is_active' => 'boolean',
    ];
}
