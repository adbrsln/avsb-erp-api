<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class AssetService extends Model
{
    use Auditable;

    protected $table = 'asset_services';

    protected $fillable = [
        'asset_id', 'service_type', 'service_date', 'next_service_date',
        'cost', 'vendor', 'description', 'document_path', 'notes',
    ];

    protected $hidden = ['document_path'];

    protected $casts = [
        'service_date' => 'date',
        'next_service_date' => 'date',
        'cost' => 'float',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
