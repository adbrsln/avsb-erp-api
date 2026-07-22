<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Invoice extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'invoices';

    protected $fillable = [
        'invoice_number', 'contract_id', 'project_id', 'credit_note_for_id', 'client',
        'client_id', 'date', 'due_date', 'status',
        'subtotal', 'sst', 'retention', 'total',
        'processed_at', 'payment_reference', 'items',
        'uuid', 'submission_status', 'submission_uid', 'long_id',
        'qr_code_url', 'submitted_at', 'last_submission_attempt',
        'submission_error', 'einvoice_type', 'einvoice_status', 'supply_date',
        'buyer_tin', 'buyer_reg_no', 'buyer_sst_reg_no', 'buyer_contact',
        'buyer_type', 'buyer_email', 'contact_phone',
        'seller_tin', 'seller_sst_reg_no', 'classification_code',
        'country', 'currency', 'einvoice_notes', 'einvoice_validated_at',
        'einvoice_xml',
    ];

    protected $hidden = ['einvoice_xml'];

    protected $casts = [
        'items' => 'array',
        'date' => 'date',
        'due_date' => 'date',
        'supply_date' => 'date',
        'subtotal' => 'float',
        'sst' => 'float',
        'retention' => 'float',
        'total' => 'float',
        'processed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'last_submission_attempt' => 'datetime',
        'einvoice_validated_at' => 'datetime',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function clientRelation()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function creditNote()
    {
        return $this->belongsTo(Invoice::class, 'credit_note_for_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(Invoice::class, 'credit_note_for_id');
    }
}
