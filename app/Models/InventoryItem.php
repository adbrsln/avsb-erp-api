<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use Auditable;
    use SoftDeletes;

    protected $table = 'inventory_items';

    protected $fillable = [
        'sku', 'name', 'category', 'unit', 'stock_qty',
        'unit_cost', 'reorder_level', 'status', 'notes',
    ];

    protected $casts = [
        'stock_qty' => 'float',
        'unit_cost' => 'float',
        'reorder_level' => 'float',
        'deleted_at' => 'datetime',
    ];

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'item_id');
    }
}
