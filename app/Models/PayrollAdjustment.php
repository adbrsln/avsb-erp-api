<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class PayrollAdjustment extends Model
{
    use Auditable;

    protected $table = 'payroll_adjustments';

    protected $fillable = [
        'payroll_run_item_id', 'type', 'label', 'amount', 'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function payrollRunItem()
    {
        return $this->belongsTo(PayrollRunItem::class, 'payroll_run_item_id');
    }

    public function creator()
    {
        return $this->belongsTo(StaffProfile::class, 'created_by');
    }
}
