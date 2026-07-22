<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxCode extends Model
{
    use Auditable, HasFactory;

    protected $table = 'tax_codes';

    protected $fillable = ['code', 'name', 'rate', 'is_active'];

    protected $casts = [
        'rate' => 'float',
        'is_active' => 'boolean',
    ];
}
