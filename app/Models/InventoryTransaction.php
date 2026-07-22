<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use Auditable;

    protected $table = 'inventory_transactions';

    protected $fillable = [
        'item_id', 'type', 'qty', 'unit_cost', 'total_cost',
        'reference_type', 'reference_id', 'notes',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_cost' => 'float',
        'total_cost' => 'float',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
