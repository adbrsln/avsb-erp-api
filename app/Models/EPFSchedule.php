<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EPFSchedule extends Model
{
    protected $table = 'epf_schedules';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code', 'name', 'employer_rate', 'employee_rate',
        'max_tier_wage', 'description',
    ];

    protected $casts = [
        'employer_rate' => 'float',
        'employee_rate' => 'float',
        'max_tier_wage' => 'float',
    ];

    public function tiers()
    {
        return $this->hasMany(EPFContributionTier::class, 'schedule_code', 'code');
    }

    public function rules()
    {
        return $this->hasMany(EPFScheduleRule::class, 'schedule_code', 'code');
    }
}
