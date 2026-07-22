<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use Auditable;

    protected $table = 'tasks';

    protected $fillable = [
        'phase_id', 'title', 'description',
        'status', 'assigned_to', 'priority',
        'start_date', 'end_date',
        'actual_start', 'actual_end',
        'pause_reason', 'paused_at',
        'pause_notes', 'completion_notes',
        'started_by', 'paused_by', 'completed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'paused_at' => 'datetime',
    ];

    public function phase()
    {
        return $this->belongsTo(Phase::class);
    }

    public function staff()
    {
        return $this->belongsToMany(StaffProfile::class, 'task_staff', 'task_id', 'staff_id');
    }

    public function documents()
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function startedBy()
    {
        return $this->belongsTo(StaffProfile::class, 'started_by');
    }

    public function pausedBy()
    {
        return $this->belongsTo(StaffProfile::class, 'paused_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(StaffProfile::class, 'completed_by');
    }
}
