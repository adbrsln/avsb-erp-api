<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubcontractorPIC extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'subcontractor_pics';

    protected $fillable = [
        'subcontractor_id', 'name', 'phone', 'email',
        'job_title', 'department', 'notes', 'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function subcontractor()
    {
        return $this->belongsTo(Subcontractor::class, 'subcontractor_id');
    }
}
