<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class ProjectMaterialUsage extends Model
{
    use Auditable;

    protected $table = 'project_material_usage';

    protected $fillable = [
        'project_id', 'phase_id', 'task_id', 'item_id',
        'qty', 'unit_cost', 'total_cost', 'notes', 'created_by',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_cost' => 'float',
        'total_cost' => 'float',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function phase()
    {
        return $this->belongsTo(Phase::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function creator()
    {
        return $this->belongsTo(StaffProfile::class, 'created_by');
    }
}
