<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffProfile extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'staff_profiles';

    protected $fillable = [
        'name', 'email', 'alternate_email', 'phone', 'identification_no', 'employee_id',
        'job_title', 'is_active',
        'date_joined', 'hire_date', 'joined_at', 'last_day', 'archive_reason',
        'worker_status', 'department', 'location', 'schedule',
        'date_of_birth', 'gender', 'race', 'nationality', 'citizenship',
        'residential_status', 'has_pr', 'marital_status', 'ability_status',
        'basic_salary', 'hourly_rate', 'salary_wage_frequency', 'payment_method',
        'bank_name', 'bank_account_no', 'account_name',
        'epf_no', 'socso_no', 'tax_no',
        'epf_contributing', 'epf_member_before_aug_1998',
        'epf_voluntary_employee_rate', 'epf_voluntary_employer_rate',
        'pcb_borne_by_employer', 'socso_contribution_type',
        'eis_contributing', 'reported_to_lhdn',
        'payroll_policy', 'payroll_cycle',
        'spouse', 'address', 'emergency_contact', 'dependent_children',
        'leave_group_id', 'socso_24h_enabled', 'socso_category',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_pr' => 'boolean',
        'epf_contributing' => 'boolean',
        'epf_member_before_aug_1998' => 'boolean',
        'pcb_borne_by_employer' => 'boolean',
        'eis_contributing' => 'boolean',
        'reported_to_lhdn' => 'boolean',
        'basic_salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'epf_voluntary_employee_rate' => 'float',
        'epf_voluntary_employer_rate' => 'float',
        'spouse' => 'array',
        'address' => 'array',
        'emergency_contact' => 'array',
        'dependent_children' => 'array',
        'socso_24h_enabled' => 'boolean',
        'hire_date' => 'date',
        'joined_at' => 'datetime',
        'last_day' => 'date',
        'date_joined' => 'date',
        'date_of_birth' => 'date',
    ];

    protected $hidden = [];

    protected $appends = ['role'];

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    public function getRoleAttribute()
    {
        $roles = $this->user?->getRoleNames();

        return $roles ? implode(', ', $roles) : 'staff';
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_staff_pics', 'staff_id', 'project_id');
    }

    public function phases()
    {
        return $this->belongsToMany(Phase::class, 'phase_staff', 'staff_id', 'phase_id');
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_staff', 'staff_id', 'task_id');
    }

    public function leaveGroup()
    {
        return $this->belongsTo(LeaveGroup::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(StaffLeaveBalance::class, 'staff_id');
    }
}
