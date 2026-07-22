<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Contract extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'contracts';

    protected $fillable = [
        'contract_number', 'project_id', 'client', 'date',
        'status', 'total_amount', 'subtotal', 'sst_rate', 'retention_rate',
        'terms', 'billing_milestones', 'items',
        'buyer_tin', 'buyer_reg_no', 'buyer_sst_reg_no', 'buyer_contact',
        'buyer_type', 'buyer_email', 'contact_phone',
    ];

    protected $casts = [
        'billing_milestones' => 'array',
        'items' => 'array',
        'date' => 'date',
        'total_amount' => 'float',
        'subtotal' => 'float',
        'sst_rate' => 'float',
        'retention_rate' => 'float',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
