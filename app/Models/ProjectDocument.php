<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class ProjectDocument extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'project_documents';

    protected $fillable = [
        'project_id', 'phase_id', 'task_id', 'uploaded_by', 'original_filename', 'stored_filename',
        'file_path', 'mime_type', 'file_size', 'category', 'notes',
    ];

    protected $hidden = ['file_path'];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function phase()
    {
        return $this->belongsTo(Phase::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function uploader()
    {
        return $this->belongsTo(StaffProfile::class, 'uploaded_by');
    }
}
