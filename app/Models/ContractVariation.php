<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class ContractVariation extends Model
{
    use Auditable;
    protected $table = 'contract_variations';

    protected $fillable = [
        'contract_id', 'variation_number', 'description', 'amount',
        'status', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'approved_at' => 'datetime',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function approver()
    {
        return $this->belongsTo(StaffProfile::class, 'approved_by');
    }
}
