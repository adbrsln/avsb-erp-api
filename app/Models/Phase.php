<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Phase extends Model
{
    use Auditable, HasFactory;

    protected $table = 'project_phases';

    protected $fillable = [
        'project_id', 'name', 'description', 'order',
        'start_date', 'end_date', 'status',
        'started_by', 'started_at', 'completed_by', 'completed_at', 'completion_remarks',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'order' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function checklistItems()
    {
        return $this->hasMany(ChecklistItem::class);
    }

    public function checklistResults()
    {
        return $this->hasMany(ChecklistResult::class);
    }

    public function startedBy()
    {
        return $this->belongsTo(StaffProfile::class, 'started_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(StaffProfile::class, 'completed_by');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function documents()
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function staff()
    {
        return $this->belongsToMany(StaffProfile::class, 'phase_staff', 'phase_id', 'staff_id');
    }

    public function comments()
    {
        return $this->hasMany(PhaseComment::class, 'phase_id')->orderBy('created_at');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
