<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class AssetMovement extends Model
{
    use Auditable;

    protected $table = 'asset_movements';

    protected $fillable = [
        'asset_id', 'movement_type', 'from_location', 'to_location',
        'from_staff_id', 'to_staff_id', 'movement_date', 'notes', 'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function fromStaff()
    {
        return $this->belongsTo(StaffProfile::class, 'from_staff_id');
    }

    public function toStaff()
    {
        return $this->belongsTo(StaffProfile::class, 'to_staff_id');
    }

    public function creator()
    {
        return $this->belongsTo(StaffProfile::class, 'created_by');
    }
}
