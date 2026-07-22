<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class SubcontractorClaim extends Model
{
    use Auditable;

    protected $table = 'subcontractor_claims';

    protected $fillable = [
        'project_subcontractor_id', 'claim_number',
        'claim_date', 'period_start', 'period_end',
        'work_done_pct', 'cumulative_pct',
        'claimed_amount', 'retention_deducted', 'net_payable',
        'previous_paid', 'current_due',
        'status',
        'submitted_by', 'submitted_at',
        'verified_by', 'verified_at',
        'rejection_reason',
        'approved_by', 'approved_at',
        'paid_at', 'payment_reference',
        'notes',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'work_done_pct' => 'float',
        'cumulative_pct' => 'float',
        'claimed_amount' => 'float',
        'retention_deducted' => 'float',
        'net_payable' => 'float',
        'previous_paid' => 'float',
        'current_due' => 'float',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function projectSubcontractor()
    {
        return $this->belongsTo(ProjectSubcontractor::class);
    }

    public function submitter()
    {
        return $this->belongsTo(StaffProfile::class, 'submitted_by');
    }

    public function verifier()
    {
        return $this->belongsTo(StaffProfile::class, 'verified_by');
    }

    public function approver()
    {
        return $this->belongsTo(StaffProfile::class, 'approved_by');
    }

    public function documents()
    {
        return $this->hasMany(SubcontractorClaimDocument::class, 'subcontractor_claim_id');
    }
}
