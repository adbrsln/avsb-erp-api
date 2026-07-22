<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use Auditable;

    protected $table = 'attendance';

    protected $fillable = [
        'staff_id', 'date', 'clock_in', 'clock_out',
        'total_hours', 'latitude', 'longitude',
        'clock_in_latitude', 'clock_in_longitude',
        'clock_out_latitude', 'clock_out_longitude',
        'clock_in_photo', 'clock_out_photo',
        'clock_in_ip', 'clock_out_ip', 'status', 'note',
        'payroll_run_item_id', 'project_id',
        'flagged', 'flagged_reason', 'flagged_cleared_by', 'flagged_cleared_at',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'total_hours' => 'float',
        'flagged' => 'boolean',
        'flagged_cleared_at' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(StaffProfile::class, 'staff_id');
    }

    public function payrollRunItem()
    {
        return $this->belongsTo(PayrollRunItem::class, 'payroll_run_item_id');
    }

    public function flagClearedBy()
    {
        return $this->belongsTo(StaffProfile::class, 'flagged_cleared_by');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
