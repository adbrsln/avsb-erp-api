<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use Auditable;

    protected $table = 'purchase_order_items';

    public $timestamps = false;

    protected $fillable = [
        'purchase_order_id', 'description', 'unit', 'quantity',
        'unit_price', 'total', 'account_id',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'total' => 'float',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
