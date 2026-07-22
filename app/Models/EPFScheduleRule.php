<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EPFScheduleRule extends Model
{
    use HasFactory;

    protected $table = 'epf_schedule_rules';

    protected $fillable = [
        'schedule_code', 'min_age', 'max_age', 'citizenship',
        'is_pr', 'elected_before_1998', 'priority',
    ];

    public $timestamps = false;
}
