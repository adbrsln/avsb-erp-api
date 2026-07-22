<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $table = 'receipts';

    protected $fillable = [
        'receipt_number', 'invoice_id', 'invoice_payment_id',
        'amount', 'date', 'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'date' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment()
    {
        return $this->belongsTo(InvoicePayment::class, 'invoice_payment_id');
    }
}
