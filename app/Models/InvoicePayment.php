<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    use Auditable;

    protected $table = 'invoice_payments';

    protected $fillable = [
        'invoice_id', 'amount', 'payment_date',
        'debit_account_id', 'credit_account_id',
        'payment_reference', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'payment_date' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function debitAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'debit_account_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'credit_account_id');
    }
}
