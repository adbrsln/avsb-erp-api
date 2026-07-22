<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EPFContributionTier extends Model
{
    use HasFactory;

    protected $table = 'epf_contribution_tiers';

    protected $fillable = [
        'schedule_code', 'wage_from', 'wage_to',
        'employer_amount', 'employee_amount',
    ];

    protected $casts = [
        'wage_from' => 'float',
        'wage_to' => 'float',
        'employer_amount' => 'float',
        'employee_amount' => 'float',
    ];

    public function schedule()
    {
        return $this->belongsTo(EPFSchedule::class, 'schedule_code', 'code');
    }
}
