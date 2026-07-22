<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class SubcontractorPIC extends Model
{
    use SoftDeletes, Auditable;

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
