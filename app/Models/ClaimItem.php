<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class ClaimItem extends Model
{
    use Auditable;
    protected $table = 'claim_items';

    protected $fillable = [
        'claim_id', 'description', 'category', 'amount',
        'receipt_url',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}
