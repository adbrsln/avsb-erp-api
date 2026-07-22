<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    use Auditable;

    protected $table = 'payroll_periods';

    protected $fillable = [
        'code', 'start_date', 'end_date', 'month', 'year', 'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'month' => 'integer',
        'year' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(PayrollRunItem::class, 'period_id');
    }
}
