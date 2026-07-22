<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SelfBilledInvoice extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'self_billed_invoices';

    protected $fillable = [
        'invoice_number', 'supplier_id', 'project_id',
        'date', 'due_date', 'supply_date', 'status',
        'subtotal', 'sst', 'retention', 'total',
        'items', 'notes',
        'uuid', 'submission_status', 'submission_uid', 'long_id',
        'qr_code_url', 'submitted_at', 'last_submission_attempt',
        'submission_error', 'einvoice_xml',
        'approved_by', 'approved_at', 'created_by',
    ];

    protected $casts = [
        'items' => 'array',
        'date' => 'date',
        'due_date' => 'date',
        'supply_date' => 'date',
        'subtotal' => 'float',
        'sst' => 'float',
        'retention' => 'float',
        'total' => 'float',
        'submitted_at' => 'datetime',
        'last_submission_attempt' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Subcontractor::class, 'supplier_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function approver()
    {
        return $this->belongsTo(StaffProfile::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(StaffProfile::class, 'created_by');
    }
}
