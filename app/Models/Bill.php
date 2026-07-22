<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use Auditable;

    protected $table = 'bills';

    protected $fillable = [
        'bill_number', 'vendor_id', 'purchase_order_id', 'vendor_bill_no',
        'bill_date', 'due_date', 'status', 'subtotal', 'tax', 'total',
        'paid_amount', 'balance', 'notes',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'float',
        'tax' => 'float',
        'total' => 'float',
        'paid_amount' => 'float',
        'balance' => 'float',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items()
    {
        return $this->hasMany(BillItem::class);
    }

    public function payments()
    {
        return $this->hasMany(BillPayment::class);
    }
}
