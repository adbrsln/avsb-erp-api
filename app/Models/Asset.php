<?php

namespace App\Models;

use App\Services\NumberingService;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use Auditable, HasFactory;
    use SoftDeletes;

    protected $table = 'assets';

    protected $fillable = [
        'asset_code', 'name', 'asset_type', 'make', 'model', 'year', 'serial_number',
        'registration_number', 'specifications', 'purchase_date', 'purchase_cost',
        'current_value', 'status', 'condition', 'warranty_expiry',
        'last_service_date', 'next_service_date', 'location', 'assigned_to',
        'purchase_order_ref', 'bill_ref', 'notes', 'created_by',
    ];

    protected $casts = [
        'specifications' => 'array',
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'last_service_date' => 'date',
        'next_service_date' => 'date',
        'purchase_cost' => 'float',
        'current_value' => 'float',
        'year' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($asset) {
            if (empty($asset->asset_code)) {
                $asset->asset_code = (new NumberingService)->generate('asset');
            }
        });
    }

    public function licenses()
    {
        return $this->hasMany(AssetLicense::class, 'asset_id');
    }

    public function movements()
    {
        return $this->hasMany(AssetMovement::class, 'asset_id');
    }

    public function services()
    {
        return $this->hasMany(AssetService::class, 'asset_id');
    }

    public function assignedStaff()
    {
        return $this->belongsTo(StaffProfile::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(StaffProfile::class, 'created_by');
    }
}
