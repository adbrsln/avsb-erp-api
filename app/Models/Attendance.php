<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use Auditable, HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'staff_id', 'date', 'clock_in', 'clock_out',
        'total_hours', 'latitude', 'longitude',
        'clock_in_latitude', 'clock_in_longitude',
        'clock_out_latitude', 'clock_out_longitude',
        'clock_in_photo', 'clock_out_photo',
        'clock_in_ip', 'clock_out_ip', 'status', 'note',
        'payroll_run_item_id', 'project_id',
        'geofence_id', 'clock_out_geofence_id',
        'flagged', 'flagged_reason', 'flagged_cleared_by', 'flagged_cleared_at',
        'schedule_flagged', 'schedule_flag_reason',
        'auto_closed', 'auto_close_reason', 'auto_closed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'total_hours' => 'float',
        'flagged' => 'boolean',
        'schedule_flagged' => 'boolean',
        'auto_closed' => 'boolean',
        'auto_closed_at' => 'datetime',
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

    public function geofence()
    {
        return $this->belongsTo(Geofence::class, 'geofence_id');
    }

    public function clockOutGeofence()
    {
        return $this->belongsTo(Geofence::class, 'clock_out_geofence_id');
    }
}
