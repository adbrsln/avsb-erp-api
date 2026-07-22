<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class EInvoiceSubmissionLog extends Model
{
    use Auditable;

    protected $table = 'einvoice_submission_logs';
    public $timestamps = false;

    protected $fillable = [
        'model_type', 'model_id', 'action',
        'request_payload', 'response_payload',
        'http_status', 'success', 'duration_ms',
    ];

    protected $casts = [
        'success' => 'boolean',
        'http_status' => 'integer',
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
    ];
}
