<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timecard extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'timecards';

    protected $fillable = [
        'staff_id', 'project_id', 'date', 'hours_worked',
        'description', 'status',
    ];

    protected $casts = [
        'date' => 'date',
        'hours_worked' => 'float',
    ];
}
