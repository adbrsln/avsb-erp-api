<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseClaim extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'claims';

    protected $fillable = [
        'claim_ref', 'staff_id', 'title', 'description', 'status',
        'total_amount', 'submitted_date', 'approver_id',
        'approved_at', 'items', 'receipt_url',
        'rejection_reason', 'rejected_at', 'paid_at', 'payment_reference',
    ];

    protected $casts = [
        'items' => 'array',
        'total_amount' => 'float',
        'submitted_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(StaffProfile::class, 'staff_id');
    }

    public function approver()
    {
        return $this->belongsTo(StaffProfile::class, 'approver_id');
    }
}
