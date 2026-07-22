<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log';

    protected $fillable = [
        'log_name', 'description', 'subject_type', 'subject_id',
        'causer_type', 'causer_id', 'properties', 'event', 'batch_uuid',
        'project_id',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function subject()
    {
        return $this->morphTo();
    }

    public function causer()
    {
        return $this->morphTo();
    }
}
