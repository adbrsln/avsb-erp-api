<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class BillPayment extends Model
{
    use Auditable;
    protected $table = 'bill_payments';

    protected $fillable = [
        'bill_id', 'amount', 'payment_date',
        'debit_account_id', 'credit_account_id',
        'payment_reference', 'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'payment_date' => 'date',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
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
