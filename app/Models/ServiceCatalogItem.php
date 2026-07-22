<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class ServiceCatalogItem extends Model
{
    use Auditable;

    protected $table = 'service_catalog_items';

    protected $fillable = [
        'name', 'description', 'unit', 'unit_rate', 'tax_code', 'category', 'is_active',
    ];

    protected $casts = [
        'unit_rate' => 'float',
        'is_active' => 'boolean',
    ];
}
