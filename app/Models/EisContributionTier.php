<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EisContributionTier extends Model
{
    protected $table = 'eis_contribution_tiers';
    public $timestamps = false;

    protected $fillable = [
        'wage_from', 'wage_to', 'employer_amount', 'employee_amount',
    ];

    protected $casts = [
        'wage_from' => 'float',
        'wage_to' => 'float',
        'employer_amount' => 'float',
        'employee_amount' => 'float',
    ];
}
