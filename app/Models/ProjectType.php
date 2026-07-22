<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class ProjectType extends Model
{
    use Auditable;

    protected $table = 'project_types';

    protected $fillable = [
        'name', 'code', 'color', 'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function phaseTemplates()
    {
        return $this->belongsToMany(PhaseTemplate::class, 'project_type_phase_template')
            ->withPivot('sort_order')
            ->orderBy('pivot_sort_order');
    }
}
