<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectClaim extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'project_claims';

    protected $fillable = [
        'claim_number', 'project_id', 'title', 'description', 'amount',
        'status', 'submitted_by', 'approved_by', 'submitted_at', 'approved_at',
        'paid_at', 'payment_reference',
        'items', 'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function getItemsAttribute($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            // Handle double-encoded data (string stored inside JSON column)
            if (is_string($decoded)) {
                $inner = json_decode($decoded, true);
                if (is_array($inner)) {
                    return $inner;
                }
            }
        }

        return [];
    }

    public function setItemsAttribute($value): void
    {
        $this->attributes['items'] = is_array($value) ? json_encode($value) : $value;
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(StaffProfile::class, 'submitted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(StaffProfile::class, 'approved_by');
    }

    public function documents()
    {
        return $this->hasMany(ProjectClaimDocument::class, 'project_claim_id');
    }
}
