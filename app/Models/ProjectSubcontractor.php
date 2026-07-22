<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class ProjectSubcontractor extends Model
{
    use Auditable;

    protected $table = 'project_subcontractors';

    protected $fillable = [
        'project_id', 'subcontractor_id', 'scope_of_work',
        'contract_value', 'retention_pct', 'retention_amount',
        'retention_released_at_cc', 'retention_released_at_dlp',
        'dlp_end_date', 'cc_date', 'status', 'assigned_by',
    ];

    protected $casts = [
        'contract_value' => 'float',
        'retention_pct' => 'float',
        'retention_amount' => 'float',
        'retention_released_at_cc' => 'float',
        'retention_released_at_dlp' => 'float',
        'dlp_end_date' => 'date',
        'cc_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function subcontractor()
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function claims()
    {
        return $this->hasMany(SubcontractorClaim::class);
    }

    public function assigner()
    {
        return $this->belongsTo(StaffProfile::class, 'assigned_by');
    }
}
