<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Timecard extends Model
{
    use SoftDeletes, Auditable;
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
