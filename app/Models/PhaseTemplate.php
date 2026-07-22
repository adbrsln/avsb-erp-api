<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class PhaseTemplate extends Model
{
    use Auditable;

    protected $table = 'phase_templates';

    protected $fillable = [
        'name', 'code', 'order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function projectTypes()
    {
        return $this->belongsToMany(ProjectType::class, 'project_type_phase_template');
    }
}
