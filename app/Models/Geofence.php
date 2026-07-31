<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Geofence extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'name', 'description', 'latitude', 'longitude',
        'radius_meters', 'is_active', 'created_by', 'project_id',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meters' => 'integer',
        'is_active' => 'boolean',
    ];

    public function createdBy()
    {
        return $this->belongsTo(StaffProfile::class, 'created_by');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
