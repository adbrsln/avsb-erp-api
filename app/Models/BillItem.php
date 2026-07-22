<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class BillItem extends Model
{
    use Auditable;
    protected $table = 'bill_items';
    public $timestamps = false;

    protected $fillable = [
        'bill_id', 'description', 'unit', 'quantity',
        'unit_price', 'total', 'account_id',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'total' => 'float',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
