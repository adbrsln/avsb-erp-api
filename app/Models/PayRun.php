<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayRun extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'pay_runs';

    protected $fillable = [
        'staff_id', 'pay_run_number', 'period_start', 'period_end', 'total_hours',
        'hourly_rate', 'gross_pay', 'deductions', 'net_pay',
        'status', 'paid_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_hours' => 'float',
        'hourly_rate' => 'float',
        'gross_pay' => 'float',
        'deductions' => 'float',
        'net_pay' => 'float',
        'paid_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class, 'staff_id');
    }
}
