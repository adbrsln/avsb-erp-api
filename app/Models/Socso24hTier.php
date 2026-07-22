<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Socso24hTier extends Model
{
    protected $table = 'socso_24h_tiers';

    public $timestamps = false;

    protected $fillable = [
        'category', 'phase', 'wage_from', 'wage_to', 'employee_amount',
    ];

    protected $casts = [
        'phase' => 'integer',
        'wage_from' => 'float',
        'wage_to' => 'float',
        'employee_amount' => 'float',
    ];
}
