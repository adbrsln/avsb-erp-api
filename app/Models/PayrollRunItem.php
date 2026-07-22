<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class PayrollRunItem extends Model
{
    use Auditable;
    protected $table = 'payroll_run_items';
    public $timestamps = false;

    protected $fillable = [
        'period_id', 'employee_id', 'salary',
        'wage_type', 'total_hours', 'hourly_rate_applied',
        'period_start', 'period_end',
        'epf_employer', 'epf_employee', 'epf_schedule_code',
        'socso_employer', 'socso_employee',
        'eis_employer', 'eis_employee',
        'socso_24h_employee',
        'paid', 'paid_at', 'paid_by',
        'confirmed', 'confirmed_at', 'confirmed_by',
    ];

    protected $casts = [
        'salary' => 'float',
        'wage_type' => 'string',
        'total_hours' => 'float',
        'hourly_rate_applied' => 'float',
        'period_start' => 'date',
        'period_end' => 'date',
        'epf_employer' => 'float',
        'epf_employee' => 'float',
        'socso_employer' => 'float',
        'socso_employee' => 'float',
        'eis_employer' => 'float',
        'eis_employee' => 'float',
        'socso_24h_employee' => 'float',
        'paid' => 'boolean',
        'paid_at' => 'datetime',
        'paid_by' => 'integer',
        'confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'period_id');
    }

    public function employee()
    {
        return $this->belongsTo(StaffProfile::class, 'employee_id');
    }

    public function adjustments()
    {
        return $this->hasMany(PayrollAdjustment::class, 'payroll_run_item_id');
    }

    public function confirmer()
    {
        return $this->belongsTo(StaffProfile::class, 'confirmed_by');
    }

    public function getNetPayAttribute(): float
    {
        return round(
            (float) ($this->salary ?? 0)
            - (float) ($this->epf_employee ?? 0)
            - (float) ($this->socso_employee ?? 0)
            - (float) ($this->eis_employee ?? 0)
            - (float) ($this->socso_24h_employee ?? 0),
            2
        );
    }
}
