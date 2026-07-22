<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class PurchaseOrder extends Model
{
    use Auditable;
    protected $table = 'purchase_orders';

    protected $fillable = [
        'po_number', 'vendor_id', 'order_date', 'delivery_date',
        'status', 'subtotal', 'tax', 'total', 'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'float',
        'tax' => 'float',
        'total' => 'float',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
