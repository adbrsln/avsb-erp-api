<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'quotations';

    protected $fillable = [
        'quote_number', 'project_id', 'client', 'date', 'valid_until', 'status',
        'subtotal', 'sst', 'sst_rate', 'retention_pct', 'retention_amount', 'total',
        'notes', 'items',
        'buyer_tin', 'buyer_reg_no', 'buyer_sst_reg_no', 'buyer_contact',
        'buyer_type', 'buyer_email', 'contact_phone',
    ];

    protected $casts = [
        'items' => 'array',
        'date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'float',
        'sst' => 'float',
        'sst_rate' => 'float',
        'retention_pct' => 'float',
        'retention_amount' => 'float',
        'total' => 'float',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
