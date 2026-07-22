<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocsoContributionTier extends Model
{
    use HasFactory;

    protected $table = 'socso_contribution_tiers';

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
