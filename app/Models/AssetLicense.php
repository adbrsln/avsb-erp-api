<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class AssetLicense extends Model
{
    use Auditable;

    protected $table = 'asset_licenses';

    protected $fillable = [
        'asset_id', 'license_type', 'license_number', 'issuing_authority',
        'issue_date', 'expiry_date', 'cost', 'status', 'document_path', 'notes',
    ];

    protected $hidden = ['document_path'];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'cost' => 'float',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
